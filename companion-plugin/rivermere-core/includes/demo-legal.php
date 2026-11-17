<?php
/**
 * Legal pages (Privacy Policy, Terms and Conditions, Cookie Policy) for the demo site.
 *
 * IMPORTANT: these are TEMPLATES describing how this software behaves. They are not legal advice.
 * Every <mark>[bracketed]</mark> item is an unknown legal fact that the real operator must supply,
 * and the documents must be reviewed by a qualified professional before production use.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

const RIVERMERE_LEGAL_VERSION = 2;

/**
 * Notice shown at the top of each demo legal page.
 *
 * @return string
 */
function rivermere_legal_notice() {
	return '<div class="notice"><p><strong>Demo document.</strong> This page belongs to a fictional demonstration website. Text highlighted like <mark>[this]</mark> is a placeholder for information only the real operator can provide (legal name, address, jurisdiction, contacts, retention periods). Having this page does not by itself make a website compliant with any law. Have it reviewed by a qualified professional before production use.</p></div>';
}

/**
 * Privacy Policy body.
 *
 * @return string
 */
function rivermere_legal_privacy() {
	return rivermere_legal_notice() . <<<'HTML'
<p><em>Last updated: <mark>[date]</mark></em></p>

<h2>1. Who we are</h2>
<p>This website is operated by <mark>[Operator legal name]</mark> ("we", "us"), trading as Rivermere Farmers Market, <mark>[registered address]</mark>. For questions about this policy or your personal information, contact <mark>[privacy contact email]</mark>.</p>

<h2>2. What information we collect</h2>
<h3>Newsletter subscriptions</h3>
<p>If you subscribe to our newsletter we store:</p>
<ul>
<li>your email address (trimmed and converted to lower case);</li>
<li>your subscription status (subscribed or unsubscribed);</li>
<li>the date and time you gave consent, and the date and time of your subscription or last change;</li>
<li>a random unsubscribe token used in the unsubscribe link we email to you.</li>
</ul>
<p>We do not store your IP address with your subscription.</p>

<h3>Vendor applications</h3>
<p>If you apply to sell at the market through the vendor application form, we store the details you enter: business name, your first and last name, email address, phone number, the products you sell, and any optional information you provide (website, social media profile, business description, product category, city or region, market experience and additional notes). We also store the time of your application, a record of your privacy consent (including the wording you agreed to and the time), the application status (new, under review, approved or rejected) and any internal notes our team adds while reviewing it. Applications are stored in this website’s WordPress database and are visible only to authorised administrators; they are not published and are not available through any public page or API. We email the market team a short notification that an application arrived (without the applicant’s personal details) and, if switched on, send you a generic acknowledgement. Submitting an application does not subscribe you to our newsletter. We do not collect payment through the application form.</p>
<h3>Technical and security data</h3>
<p>To limit abuse of the subscription form we keep a short-lived counter (up to one hour) linked to a one-way hash of your IP address. The IP address itself is not stored by this feature. Our web server and hosting provider may also keep standard access logs (such as IP address, browser type and pages requested) for security and troubleshooting: <mark>[hosting provider and log retention period]</mark>.</p>

<h3>Anonymous analytics</h3>
<p>If you accept <strong>analytics cookies</strong> in our cookie settings, our own analytics tool (Rivermere Insights) counts page views, clicks on key buttons and links (such as calls to action, telephone, email, WhatsApp, social and external links) and completed vendor applications and newsletter sign-ups. These are stored as daily totals in this website’s database. They do not include names, email addresses, phone numbers, anything you type into a form, your IP address or a browser fingerprint, and the tool does not set a cookie or assign you a visitor ID, so the figures are counts, not profiles of individuals. If you reject analytics cookies, or withdraw consent, none of this is recorded. Daily totals are deleted after <mark>[retention period, default 365 days]</mark>. If Google Analytics is also enabled for this website, it is loaded only after you accept analytics cookies (see the Cookie Policy). <mark>[Confirm which analytics tools are actually enabled]</mark></p>
<h3>Cookies and similar technologies</h3>
<p>We use a small first-party cookie to remember your cookie choices. Optional analytics cookies are used only if analytics is switched on for this website and you have agreed. See our <a href="/cookie-policy/">Cookie Policy</a> for details.</p>

<h3>Messages you send us</h3>
<p>If you email or phone us, we keep the details you provide for as long as needed to answer you and to keep a record of the conversation: <mark>[retention period]</mark>. This website does not currently include a contact form.</p>

<h2>3. Why we use your information and our legal basis</h2>
<ul>
<li><strong>Sending market updates</strong> (newsletter): your consent, given by ticking the consent box. You can withdraw it at any time.</li>
<li><strong>Keeping the site secure and working</strong>: our legitimate interest in protecting the website.</li>
<li><strong>Analytics (if enabled)</strong>: your consent, given through the cookie settings.</li>
<li><strong>Reviewing vendor applications</strong>: taking steps at your request before entering into an arrangement with you, supported by the consent you give on the form. <mark>[confirm legal basis for your jurisdiction]</mark></li>
<li><strong>Answering enquiries</strong>: our legitimate interest in responding to you. <mark>[confirm legal bases for your jurisdiction]</mark></li>
</ul>

<h2>4. Consent and how to unsubscribe</h2>
<p>The consent box on the newsletter form is never pre-ticked, and the form cannot be submitted without it. After you subscribe we send a confirmation email containing an unsubscribe link. Every message we send includes that link. Opening the link asks you to confirm, then marks your address as unsubscribed. You can also contact us to be removed. We do not use double opt-in verification at present.</p>

<h2>5. Who we share information with</h2>
<p>We do not sell your personal information. We share it only with service providers who help us run the website and deliver email (for example when we notify the market team about an application), such as <mark>[hosting provider]</mark> and <mark>[email delivery provider]</mark>, under appropriate agreements. If analytics is enabled and you consent, anonymised usage data is processed by <mark>[analytics provider, e.g. Google Analytics 4]</mark>. Links to external services (maps, social networks, share buttons) take you to sites with their own privacy policies; we do not load their scripts on this website.</p>

<h2>6. How long we keep information</h2>
<ul>
<li>Newsletter subscribers: until you unsubscribe or ask us to delete your data. After you unsubscribe we keep your address marked as unsubscribed for <mark>[period]</mark> so we do not email you again, then delete it.</li>
<li>Vendor applications: for as long as needed to review and respond to your application and for <mark>[period]</mark> afterwards, unless you ask us to delete them sooner.</li>
<li>Anonymous analytics totals: <mark>[retention period, default 365 days]</mark>.</li>
<li>Rate-limit counters: up to one hour.</li>
<li>Cookie choices: stored in your browser for up to 180 days, then asked again.</li>
<li>Server logs: <mark>[period]</mark>.</li>
</ul>

<h2>7. Security</h2>
<p>We protect the website with measures including restricted administrator access, form protection (security tokens, spam traps and rate limiting), parameterised database queries and <mark>[HTTPS and hosting security measures to be confirmed]</mark>. Subscriber lists are visible only to authorised administrators. No method of transmission or storage is completely secure, and we cannot guarantee absolute security.</p>

<h2>8. Your rights</h2>
<p>Depending on where you live, you may have the right to access the personal information we hold about you, correct it, delete it, restrict or object to its use, receive a copy in a portable format, withdraw consent at any time and complain to your local data protection authority. <mark>[List the rights and authority that apply in the operator's jurisdiction]</mark></p>

<h2>9. How to make a request</h2>
<p>To access, correct or delete your information (including a vendor application or a newsletter subscription), email <mark>[privacy contact email]</mark> from the address you used to subscribe. We may need to verify your identity and will respond within <mark>[response period]</mark>.</p>

<h2>10. Children</h2>
<p>This website is not directed at children and we do not knowingly collect their personal information.</p>

<h2>11. Changes to this policy</h2>
<p>We may update this policy as the website or the law changes. The date at the top shows when it was last revised, and material changes will be highlighted on this page.</p>

<h2>12. Contact</h2>
<address><mark>[Operator legal name]</mark><br><mark>[Address]</mark><br><mark>[Email]</mark> · <mark>[Phone]</mark></address>
HTML;
}

/**
 * Terms and Conditions body.
 *
 * @return string
 */
function rivermere_legal_terms() {
	return rivermere_legal_notice() . <<<'HTML'
<p><em>Last updated: <mark>[date]</mark></em></p>

<h2>1. About these terms</h2>
<p>By using this website you agree to these terms. The website is operated by <mark>[Operator legal name]</mark> ("we", "us"), <mark>[registered address]</mark>. If you do not agree, please do not use the site.</p>

<h2>2. Use of the website</h2>
<p>You may browse and use the website for personal, non-commercial purposes. You agree not to misuse it, attempt to gain unauthorised access, interfere with its operation, send spam through its forms, or copy content in bulk by automated means without our permission.</p>

<h2>3. Informational nature of the content</h2>
<p>The website provides general information about the market, its vendors and its events. We take reasonable care to keep it accurate, but we do not guarantee that it is complete, current or error-free. Nothing on this site is professional advice.</p>

<h2>4. Vendor information</h2>
<p>Vendor names, products, descriptions and images are provided by or about independent vendors. Vendors are responsible for their own products, prices, quality, allergen information and legal compliance. Vendor availability changes with the season, and a listing is not a guarantee that a vendor will attend on a given day. Please check allergen and ingredient information directly with the vendor.</p>

<h3>Vendor applications</h3>
<p>Submitting a vendor application does not guarantee a place at the market. We review applications individually, may ask for more information, and may approve or decline an application at our discretion. You must provide accurate information and hold any permits, licences or insurance that apply to your products. Application information is handled as described in the <a href="/privacy-policy/">Privacy Policy</a>.</p>
<h2>5. Event information, changes and cancellations</h2>
<p>Dates, times, locations, schedules and activities are correct to the best of our knowledge at the time of publication and may change because of weather, safety, availability or other circumstances. We may postpone, change or cancel events. Where possible we will update the event page and its status. Please check the event page before travelling. Unless we say otherwise, we are not liable for travel or other costs you incur if an event changes.</p>

<h2>6. Newsletter</h2>
<p>Subscribing to the newsletter is optional and subject to our <a href="/privacy-policy/">Privacy Policy</a>. You can unsubscribe at any time using the link in our emails.</p>

<h2>7. Intellectual property</h2>
<p>The website's design, text, logos and software are owned by us or our licensors and are protected by intellectual property laws. Vendor logos and photographs remain the property of their owners. You may share links to our pages and quote short extracts with attribution; any other use needs our written permission. <mark>[Confirm ownership and licences of all images used on the real site]</mark></p>

<h2>8. External links</h2>
<p>The website may link to third-party websites, such as maps, social networks and vendor sites. We do not control and are not responsible for their content, availability or privacy practices.</p>

<h2>9. Limitation of liability and disclaimers</h2>
<p>The website is provided "as is" and "as available". To the extent permitted by law, we exclude liability for losses arising from your use of, or inability to use, the website, and for the content or conduct of third parties. Nothing in these terms excludes liability that cannot be excluded by law. <mark>[Have this section reviewed for the operator's jurisdiction]</mark></p>

<h2>10. Governing law</h2>
<p>These terms are governed by the laws of <mark>[jurisdiction]</mark>, and the courts of <mark>[jurisdiction]</mark> have jurisdiction over any dispute, unless local consumer law says otherwise.</p>

<h2>11. Changes to these terms</h2>
<p>We may update these terms from time to time. The updated version applies from the date shown at the top of this page. Continued use of the website means you accept the updated terms.</p>

<h2>12. Contact</h2>
<address><mark>[Operator legal name]</mark><br><mark>[Address]</mark><br><mark>[Email]</mark> · <mark>[Phone]</mark></address>
HTML;
}

/**
 * Cookie Policy body.
 *
 * @return string
 */
function rivermere_legal_cookies() {
	return rivermere_legal_notice() . <<<'HTML'
<p><em>Last updated: <mark>[date]</mark></em></p>

<h2>1. What are cookies?</h2>
<p>Cookies are small text files that a website stores in your browser. They are used to remember choices, keep sites working and, if you agree, measure how a site is used. Similar technologies such as local storage work in a comparable way, and we refer to all of them as "cookies" here.</p>

<h2>2. The cookies this website uses</h2>
<table>
<thead><tr><th>Name</th><th>Category</th><th>Purpose</th><th>Duration</th></tr></thead>
<tbody>
<tr><td><code>rm_consent</code></td><td>Strictly necessary</td><td>Remembers your cookie choices (version, analytics, marketing, timestamp). Set by us.</td><td>180 days</td></tr>
<tr><td><code>wordpress_*</code>, <code>wp-settings-*</code></td><td>Strictly necessary</td><td>Used only when staff log in to manage the website. Not set for ordinary visitors.</td><td>Session to 14 days</td></tr>
<tr><td>(none)</td><td>Analytics (optional)</td><td>Rivermere Insights, our own aggregate analytics, runs only if you accept analytics cookies. It records daily totals and <strong>does not set any cookie or local storage and assigns no visitor ID</strong>.</td><td>n/a</td></tr>
<tr><td><code>_ga</code>, <code>_ga_*</code></td><td>Analytics (optional)</td><td>Used by Google Analytics 4 to count visits and understand which pages are useful. Set only if analytics is configured for this website and you accept analytics cookies. <mark>[Confirm whether analytics is enabled]</mark></td><td>Up to 2 years</td></tr>
</tbody>
</table>

<h2>3. Strictly necessary cookies</h2>
<p>These are required for the website to remember your cookie choices and, for staff, to log in securely. They do not need your consent and cannot be switched off.</p>

<h2>4. Analytics cookies</h2>
<p>If you accept analytics cookies, we use anonymous counts (page views, key clicks and completed sign-ups or applications) to understand how visitors use the site so we can improve it. If a third-party analytics service such as Google Analytics is configured for this website, it is also loaded only at that point. They are <strong>off by default</strong>. No analytics script is loaded until you choose "Accept all" or enable analytics in the cookie settings.</p>

<h2>5. Marketing cookies</h2>
<p>This website does not currently use marketing or advertising cookies. If that changes, they will also be off by default and will load only after you agree.</p>

<h2>6. Third-party cookies</h2>
<p>Analytics cookies, if enabled, are set by Google under its own privacy policy. This website does not embed maps, videos or social media widgets that would set third-party cookies; links to such services open on their own sites, which may set their own cookies.</p>

<h2>7. Your choices and how to change them</h2>
<p>When you first visit, a banner lets you accept all optional cookies, reject them or choose by category. You can change your mind at any time using the <strong>Cookie settings</strong> link in the footer of every page. If you withdraw consent, the website stops loading optional scripts and removes analytics cookies it can reach on this domain. Cookies already set by third parties may remain until they expire, so you may also want to clear them in your browser settings. Most browsers also let you block or delete cookies; doing so may affect some features.</p>

<h2>8. Contact</h2>
<p>Questions about cookies? Contact <mark>[contact email]</mark>. For information about personal data more broadly, see our <a href="/privacy-policy/">Privacy Policy</a>.</p>

<h2>9. Changes</h2>
<p>We will update this policy if we change the cookies we use. When the cookie categories change, we will ask for your choices again.</p>
HTML;
}

/**
 * Create or update the legal pages (idempotent) and wire them into WordPress.
 *
 * @return string[] Log.
 */
function rivermere_demo_legal() {
	$log   = array();
	$pages = array(
		'privacy-policy'       => array( 'Privacy Policy', 'rivermere_legal_privacy' ),
		'terms-and-conditions' => array( 'Terms and Conditions', 'rivermere_legal_terms' ),
		'cookie-policy'        => array( 'Cookie Policy', 'rivermere_legal_cookies' ),
	);
	$ids   = array();
	foreach ( $pages as $slug => $page ) {
		$content = call_user_func( $page[1] );
		$id      = rivermere_demo_find( 'page-' . $slug, 'page' );
		if ( ! $id ) {
			$existing = get_page_by_path( $slug );
			$id       = $existing ? $existing->ID : 0;
		}
		if ( ! $id ) {
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page[0],
					'post_name'    => $slug,
					'post_content' => $content,
				)
			);
			update_post_meta( $id, '_rivermere_demo', 'page-' . $slug );
			update_post_meta( $id, '_rivermere_legal_v', RIVERMERE_LEGAL_VERSION );
			$log[] = 'Created page: ' . $page[0];
		} elseif ( (int) get_post_meta( $id, '_rivermere_legal_v', true ) < RIVERMERE_LEGAL_VERSION ) {
			wp_update_post( array( 'ID' => $id, 'post_title' => $page[0], 'post_content' => $content ) );
			update_post_meta( $id, '_rivermere_demo', 'page-' . $slug );
			update_post_meta( $id, '_rivermere_legal_v', RIVERMERE_LEGAL_VERSION );
			$log[] = 'Updated page: ' . $page[0];
		}
		$ids[ $slug ] = (int) $id;
	}

	// Native WordPress privacy policy page.
	if ( ! empty( $ids['privacy-policy'] ) && (int) get_option( 'wp_page_for_privacy_policy' ) !== $ids['privacy-policy'] ) {
		update_option( 'wp_page_for_privacy_policy', $ids['privacy-policy'] );
		$log[] = 'Set Privacy Policy as the WordPress privacy page';
	}

	// Legal menu (footer, "legal" location), created once.
	if ( ! wp_get_nav_menu_object( 'Legal' ) ) {
		$menu_id = wp_create_nav_menu( 'Legal' );
		if ( ! is_wp_error( $menu_id ) ) {
			foreach ( $ids as $page_id ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $page_id,
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}
			$locations          = get_theme_mod( 'nav_menu_locations', array() );
			$locations['legal'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
			$log[] = 'Created Legal menu';
		}
	}

	// Theme Settings: link the cookie policy and privacy page for the banner and newsletter.
	if ( function_exists( 'update_field' ) ) {
		if ( ! empty( $ids['cookie-policy'] ) && ! get_field( 'cookie_policy_page', 'option' ) ) {
			update_field( 'cookie_policy_page', get_permalink( $ids['cookie-policy'] ), 'option' );
		}
		if ( ! empty( $ids['privacy-policy'] ) ) {
			update_field( 'privacy_page', get_permalink( $ids['privacy-policy'] ), 'option' );
		}
	}
	return $log;
}
