<!doctype html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!-- Consider adding a manifest.appcache: h5bp.com/d/Offline -->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <!-- Use the .htaccess and remove these lines to avoid edge case issues.
 More info: h5bp.com/b/378 -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>Duet Project Management </title>
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Mobile viewport optimized: h5bp.com/viewport -->
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="stylesheet" type="text/css" href="client/plugins/3rd-party/morris/morris.css">
    <link rel="stylesheet" type="text/css" href="client/plugins/3rd-party/fullcalendar-2.0.1/fullcalendar.css">
    <link rel="stylesheet" type="text/css" href="client/plugins/3rd-party/select2-3.4.8/select2.css">
    <link rel="stylesheet" type="text/css" href="client/plugins/3rd-party/nano-scroller/nanoscroller.css">
    <link rel="stylesheet" type="text/css" href="client/css/style.css">
    <link rel="stylesheet" type="text/css" href="client/plugins/document-viewer/css/style.css">



</head>

<body class="full">
<div id="hidden"></div>

<div id="app-loading-screen">
    <section class="section">
        <span class="loader loader-quart">
              <span class="loader-text">Loading</span>
        </span>

    </section>
</div>

<!-- JavaScript at the bottom for fast page loading -->

<!-- Grab Google CDN's jQuery, with a protocol relative URL; fall back to local if offline -->
<?php //TODO:Uncomment this - jquery cdn and stripe ?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="client/js/libs/jquery.min.js"><\/script>')</script>
<script defer src="client/js/libs/jquery-ui-1.10.4.custom.min.js"></script>
<script defer type="text/javascript" src="https://js.stripe.com/v1/"></script>

<!-- scripts concatenated and minified via build script -->

<script defer src="client/plugins/3rd-party/select2-3.4.8/select2.min.js"></script>
<script defer src="client/plugins/document-viewer/libs/yepnope.1.5.3-min.js"></script>
<script defer src="client/plugins/document-viewer/src/plugins.js"></script>
<script defer src="client/plugins/document-viewer/src/ttw-document-viewer.js"></script>
<script defer src="client/plugins/fluid-list/fluid-list.js"></script>
<script defer src='client/plugins/3rd-party/moment/moment.min.js'></script>
<script defer src='client/plugins/3rd-party/fullcalendar-2.0.1/fullcalendar.js'></script>
<?php //TOdo:hogan should be a library not a plugin ?>
<script defer src='client/plugins/3rd-party/handlebars/handlebars-v1.3.0.js'></script>

<!--modfified version of the dateinput script to fix positioning issue -->
<!-- https://github.com/jquerytools/jquerytools/issues/898 -->
<script defer src='client/js/libs/jquery-migrate-1.2.1.js'></script>
<script defer src='client/js/libs/jquery.tools.se.js'></script>


<!-- NVD3 Reporting -->
<script defer src="client/plugins/3rd-party/raphael/raphael-min.js"></script>
<script defer src="client/plugins/3rd-party/morris/morris.min.js"></script>


<!-- Blueimp Ajax Uploads -->
<script defer src="client/plugins/3rd-party/blueimp-jQuery-File-Upload/js/jquery.iframe-transport.js"></script>
<script defer src="client/plugins/3rd-party/blueimp-jQuery-File-Upload/js/jquery.fileupload.js"></script>


<script defer src="client/plugins/3rd-party/nano-scroller/ttw.jquery.nanoscroller.js"></script>
<script defer src="client/plugins/3rd-party/ckeditor/ckeditor.js"></script>

<script defer src="client/plugins/general-plugins.js"></script>

<script defer src="client/js/libs/underscore-min.js"></script> <!-- TODO: I really don't want all of this just for the router module -->
<script defer src="client/js/duet.utils.js"></script>
<script defer src="client/js/duet.misc.js"></script>
<script defer src="client/js/duet.options.js"></script>
<script defer src="client/js/duet.core.js"></script>
<script defer src="client/js/duet.main.js"></script>
<script defer src="client/js/duet.collections.js"></script>
<script defer src="client/js/duet.models.js"></script>
<script defer src="client/js/duet.routes.js"></script>
<script defer src="client/js/duet.helpers.js"></script>
<script defer src="client/js/duet.views.js"></script>
<script defer src="client/js/duet.stripe.js"></script>

<script defer type="text/javascript">

</script>
<!-- end scripts -->
</body>
</html>

