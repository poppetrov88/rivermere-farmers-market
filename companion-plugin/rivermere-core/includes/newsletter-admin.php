<?php
/**
 * Newsletter admin: subscribers list (search, filter, delete) and CSV export.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Subscribers table.
 */
class Rivermere_Subscribers_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'subscriber',
				'plural'   => 'subscribers',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox">',
			'email'      => __( 'Email', 'rivermere-core' ),
			'status'     => __( 'Status', 'rivermere-core' ),
			'consent_at' => __( 'Consent given', 'rivermere-core' ),
			'created_at' => __( 'Subscribed', 'rivermere-core' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'email'      => array( 'email', false ),
			'status'     => array( 'status', false ),
			'created_at' => array( 'created_at', true ),
		);
	}

	/**
	 * Checkbox column.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return '<input type="checkbox" name="subscriber[]" value="' . esc_attr( $item->id ) . '">';
	}

	/**
	 * Email column with row actions.
	 *
	 * @param object $item Row.
	 * @return string
	 */
	protected function column_email( $item ) {
		$url     = wp_nonce_url(
			add_query_arg(
				array(
					'page'       => 'rivermere-subscribers',
					'action'     => 'delete',
					'subscriber' => (int) $item->id,
				),
				admin_url( 'admin.php' )
			),
			'rm_delete_subscriber_' . (int) $item->id
		);
		$actions = array(
			'delete' => '<a href="' . esc_url( $url ) . '" onclick="return confirm(\'' . esc_js( __( 'Remove this subscriber permanently?', 'rivermere-core' ) ) . '\');">' . esc_html__( 'Delete', 'rivermere-core' ) . '</a>',
		);
		return '<strong>' . esc_html( $item->email ) . '</strong>' . $this->row_actions( $actions );
	}

	/**
	 * Default column renderer.
	 *
	 * @param object $item        Row.
	 * @param string $column_name Column.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		if ( 'status' === $column_name ) {
			return 'subscribed' === $item->status ? esc_html__( 'Subscribed', 'rivermere-core' ) : esc_html__( 'Unsubscribed', 'rivermere-core' );
		}
		return esc_html( get_date_from_gmt( $item->$column_name, 'Y-m-d H:i' ) );
	}

	/**
	 * Views (status filter).
	 *
	 * @return array
	 */
	protected function get_views() {
		global $wpdb;
		$table   = rivermere_newsletter_table();
		$current = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$counts  = $wpdb->get_results( "SELECT status, COUNT(*) AS c FROM {$table} GROUP BY status", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$all     = 0;
		foreach ( $counts as $row ) {
			$all += (int) $row->c;
		}
		$link  = static function ( $status, $label, $count, $current ) {
			$url = add_query_arg( array( 'page' => 'rivermere-subscribers', 'status' => $status ), admin_url( 'admin.php' ) );
			return sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( $url ), $current === $status ? ' class="current"' : '', esc_html( $label ), $count );
		};
		return array(
			'all'          => $link( '', __( 'All', 'rivermere-core' ), $all, $current ),
			'subscribed'   => $link( 'subscribed', __( 'Subscribed', 'rivermere-core' ), isset( $counts['subscribed'] ) ? (int) $counts['subscribed']->c : 0, $current ),
			'unsubscribed' => $link( 'unsubscribed', __( 'Unsubscribed', 'rivermere-core' ), isset( $counts['unsubscribed'] ) ? (int) $counts['unsubscribed']->c : 0, $current ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array( 'delete' => __( 'Delete', 'rivermere-core' ) );
	}

	/**
	 * Query and prepare rows.
	 */
	public function prepare_items() {
		global $wpdb;
		$table    = rivermere_newsletter_table();
		$per_page = 20;
		$paged    = max( 1, $this->get_pagenum() );
		$where    = array( '1=1' );
		$args     = array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list filters.
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		if ( in_array( $status, array( 'subscribed', 'unsubscribed' ), true ) ) {
			$where[] = 'status = %s';
			$args[]  = $status;
		}
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		if ( '' !== $search ) {
			$where[] = 'email LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';
		}
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order   = isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';
		// phpcs:enable
		if ( ! in_array( $orderby, array( 'email', 'status', 'created_at' ), true ) ) {
			$orderby = 'created_at';
		}

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $count_sql, $args ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$sql   = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query = $wpdb->prepare( $sql, array_merge( $args, array( $per_page, ( $paged - 1 ) * $per_page ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->items = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
			)
		);
	}
}

/**
 * Admin menu.
 */
function rivermere_subscribers_menu() {
	add_menu_page(
		__( 'Newsletter Subscribers', 'rivermere-core' ),
		__( 'Newsletter Subscribers', 'rivermere-core' ),
		'manage_options',
		'rivermere-subscribers',
		'rivermere_subscribers_page',
		'dashicons-email-alt',
		58
	);
}
add_action( 'admin_menu', 'rivermere_subscribers_menu' );

/**
 * Handle deletes before output.
 */
function rivermere_subscribers_handle_actions() {
	if ( ! isset( $_GET['page'] ) || 'rivermere-subscribers' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $wpdb;
	$table = rivermere_newsletter_table();
	$done  = false;

	// Single delete (row action).
	if ( isset( $_GET['action'], $_GET['subscriber'] ) && 'delete' === $_GET['action'] && ! is_array( $_GET['subscriber'] ) ) {
		$id = absint( $_GET['subscriber'] );
		check_admin_referer( 'rm_delete_subscriber_' . $id );
		$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		$done = 1;
	}

	// Bulk delete.
	$bulk = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
	if ( 'delete' === $bulk && ! empty( $_POST['subscriber'] ) && is_array( $_POST['subscriber'] ) ) {
		check_admin_referer( 'bulk-subscribers' );
		$ids = array_filter( array_map( 'absint', wp_unslash( $_POST['subscriber'] ) ) );
		foreach ( $ids as $id ) {
			$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		}
		$done = count( $ids );
	}

	if ( $done ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'rivermere-subscribers', 'deleted' => (int) $done ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
add_action( 'admin_init', 'rivermere_subscribers_handle_actions' );

/**
 * Render list page.
 */
function rivermere_subscribers_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'rivermere-core' ) );
	}
	$table = new Rivermere_Subscribers_Table();
	$table->prepare_items();
	$export = wp_nonce_url( admin_url( 'admin-post.php?action=rivermere_export_subscribers' ), 'rm_export_subscribers' );
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Newsletter Subscribers', 'rivermere-core' ); ?></h1>
		<a href="<?php echo esc_url( $export ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'rivermere-core' ); ?></a>
		<hr class="wp-header-end">
		<?php if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d subscriber deleted.', '%d subscribers deleted.', absint( $_GET['deleted'] ), 'rivermere-core' ), absint( $_GET['deleted'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
		<?php endif; ?>
		<p class="description"><?php esc_html_e( 'Subscribers opt in with a consent checkbox (single opt-in). Every confirmation email contains an unsubscribe link. Subscriber data is only visible to administrators.', 'rivermere-core' ); ?></p>
		<?php $table->views(); ?>
		<form method="get">
			<input type="hidden" name="page" value="rivermere-subscribers">
			<?php $table->search_box( __( 'Search emails', 'rivermere-core' ), 'subscriber' ); ?>
		</form>
		<form method="post">
			<?php $table->display(); ?>
		</form>
	</div>
	<?php
}

/**
 * CSV export (admins only, nonce-protected, formula-injection safe).
 */
function rivermere_export_subscribers() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export subscribers.', 'rivermere-core' ), 403 );
	}
	check_admin_referer( 'rm_export_subscribers' );
	global $wpdb;
	$table = rivermere_newsletter_table();
	$rows  = $wpdb->get_results( "SELECT email, status, consent_at, created_at FROM {$table} ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="subscribers-' . gmdate( 'Y-m-d' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'email', 'status', 'consent_at_utc', 'subscribed_at_utc' ) );
	foreach ( $rows as $row ) {
		$row = array_map(
			static function ( $cell ) {
				return preg_match( '/^[=+\-@\t\r]/', $cell ) ? "'" . $cell : $cell;
			},
			$row
		);
		fputcsv( $out, $row );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_rivermere_export_subscribers', 'rivermere_export_subscribers' );
