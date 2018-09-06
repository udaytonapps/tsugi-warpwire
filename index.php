<?php
require_once "../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/

use \Tsugi\Util\Net;
use \Tsugi\Util\U;
use \Tsugi\Util\PS;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

$LTI = LTIX::requireData();

$oldw = Settings::linkGet('w', false);
// Handle the incoming post first
$neww = U::get($_POST,'w',false);
if ( $neww && PS::s($neww)->startsWith('http://') || PS::s($neww)->startsWith('https://') ) {
    $_SESSION['error'] = __('Please enter a Warpwire Asset ID, not a Warpwire URL');
    header('Location: '.addSession('index') ) ;
    return;
}

if ( isset($LINK->id) && SettingsForm::handleSettingsPost() ) {
    if ( $neww && $neww !== $oldw ) {
        $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}warpwire_views WHERE link_id = :LI",
            array(':LI' => $LINK->id)
        );
        $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}warpwire_views_user WHERE link_id = :LI",
            array(':LI' => $LINK->id)
        );
        $_SESSION['success'] = __('Video ID changed, view tracking analytics reset.');
    }
    header('Location: '.addSession('index') ) ;
    return;
}

// Get the video
$w = Settings::linkGet('w', false);
if ( ! $w ) $w = isset($_GET['w']) ? $_GET['w'] : false;
if ( ! $w ) $w = isset($_SESSION['w']) ? $_SESSION['w'] : false;
if ( $w ) $_SESSION['w'] = $w;
$grade = Settings::linkGet('grade', false);
$warpwireurl = Settings::linkGet('warpwireurl', false);

// Render view
$OUTPUT->header();
// https://www.h3xed.com/web-development/how-to-make-a-responsive-100-width-youtube-iframe-embed
?>
<style>
.container {
    position: relative;
    overflow: hidden;
    padding-top: 56.25%;
}
.video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
</style>
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();
// https://codepen.io/team/css-tricks/pen/pvamy
// https://css-tricks.com/seamless-responsive-photo-grid/

if ( isset($LTI->user) && $LTI->user->instructor ) {
echo "<p style='text-align:right;'>";
if ( $CFG->launchactivity ) {
    echo('<a href="analytics" class="btn btn-default">Launches</a> ');
}
echo('<a href="views" class="btn btn-default">Views</a> ');
SettingsForm::button(false);
SettingsForm::start();
SettingsForm::text('w','Please enter a Warpwire video ID.  If you change the video ID, time-based view tracking will be reset.');
SettingsForm::text('warpwireurl', 'Please enter your institution\'s Warpwire url e.g. sandbox.warpwire.com');
SettingsForm::checkbox('grade','Give the student a 100% grade as soon as they view this video.');
SettingsForm::checkbox('watched','Give the student a grade from 0-100% based on the time spent viewing this video.');
SettingsForm::end();
$OUTPUT->flashMessages();
}
if ( ! $w || ! $warpwireurl ) {
    echo("<p>Video has not yet been configured</p>\n");
    $OUTPUT->footer();
    return;
}
?>
<div class="container">
<?php
if ( isset($LTI->link) && $LTI->link ) {
    if ( $grade && $LTI->result && $LTI->result->id && $RESULT->grade < 1.0 ) {
        $RESULT->gradeSend(1.0, false);
    }
}
if ( ! isset($USER->id) || ! isset($LINK->id) ) {
    echo('<div id="player" class="video">&nbsp;</div>');
} else {
?>
<iframe src="//<?= $warpwireurl ?>/w/<?= urlencode($w) ?>"
frameborder="0" allowfullscreen class="video" id="wwvideo" data-ww-id="wwvideo"></iframe>
<?php
}
?>
</div>
<?php

// Turn off translate for non-instructors since there is no UI
if ( ! isset($USER) || ! $USER->instructor ) {
    $CFG->google_translate = false;
}

$OUTPUT->footerStart();
if ( isset($USER->id) && isset($LINK->id) ) {
?>
<script>
VIDEO_ID = "<?= urlencode($w) ?>";
TRACKING_URL = "<?= addSession('tracker.php') ?>";
</script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script src="video.js?v=<?=rand()?>"></script>
<?php
}
$OUTPUT->footerEnd();
