<?php
    if ( ! defined( 'ABSPATH' ) ) {
      exit;
    }
?>

<div class="wrap">
    <div class="js-notices"></div>

    <div class="headline" style="color: #333; margin: 3.5em auto 1.5em; line-height: 1.5em; font-size: 2em; max-width: 500px; text-align: center;">Please update your Two Tap tokens first.</div>
    <div class="headline-2" style="color: #333; margin: 1.5em auto; line-height: 1.5em; font-size: 1em; max-width: 500px; text-align: center;">You can <a href="<?=admin_url( 'index.php?page=twotap-setup' )?>" title="Re-install Two Tap plugin">run the install process again</a> or update your tokens on the <a href="<?=site_url('/wp-admin/admin.php?page=' . TT_SETTINGS_PAGE . '&tab=api')?>">settings page</a>.</div>
</div>