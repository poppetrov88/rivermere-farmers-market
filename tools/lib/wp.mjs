// Test helper: run PHP inside WordPress via the Local WP-CLI wrapper (env WP_PS1 = path to wp.ps1).
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const PS1 = process.env.WP_PS1;

export const wpEval = (php) => {
	if (!PS1) {
		throw new Error('Set WP_PS1 to the wp.ps1 wrapper path');
	}
	const file = path.join(os.tmpdir(), `rm-test-${process.pid}-${Date.now()}.php`);
	fs.writeFileSync(file, `<?php\n${php}\n`);
	try {
		return execFileSync('powershell', ['-NoProfile', '-File', PS1, 'eval-file', file], { encoding: 'utf8' }).trim();
	} finally {
		fs.unlinkSync(file);
	}
};

export const wpCli = (...args) => execFileSync('powershell', ['-NoProfile', '-File', PS1, ...args], { encoding: 'utf8' }).trim();

/** Current analytics + storage counters. */
export const probe = () =>
	JSON.parse(
		wpEval(`global $wpdb; $t = ri_table();
$ev = $wpdb->get_results("SELECT event_type, SUM(hits) n FROM $t GROUP BY event_type");
$o = array(); foreach ($ev as $r) { $o[$r->event_type] = (int) $r->n; }
echo wp_json_encode(array('events'=>$o,'apps'=>(int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='vendor_application'"),'subs'=>(int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rm_subscribers")));`),
	);

export const clearThrottles = () =>
	wpEval(`global $wpdb; $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%rm\\\\_app\\\\_%' OR option_name LIKE '%rm\\\\_nl\\\\_%' OR option_name LIKE '%ri\\\\_rl\\\\_%' OR option_name LIKE '%rm\\\\_appid\\\\_%'"); wp_cache_flush(); echo 'ok';`);
