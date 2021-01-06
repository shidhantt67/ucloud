<?php
// increase time limit to 30 minutes
set_time_limit(30*60);

// includes and security
include_once('_local_auth.inc.php');

if (!isset($_REQUEST['languageId']))
{
    die('Could not find language id.');
}
else
{
    $languageId = (int) $_REQUEST['languageId'];
}
?>

<html lang="en-us">

    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1" >
        <meta charset="utf-8" />
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/responsive.css" rel="stylesheet">
        <link href="<?php echo ADMIN_WEB_ROOT; ?>/assets/css/custom.css" rel="stylesheet">
    </head>
    <body style="background: #ffffff;">

<p>Getting English content in preparation for automatic translation...</p>
<?php
$languageItem = $db->getRow("SELECT languageName, language_code FROM language WHERE id = ".(int)$languageId." LIMIT 1");
$languageData = $db->getRows("SELECT language_content.id, language_content.is_locked, language_content.content, language_key.languageKey, language_key.id AS languageKeyId, language_key.defaultContent FROM language_content LEFT JOIN language_key ON language_content.languageKeyId = language_key.id LEFT JOIN language ON language_content.languageId = language.id WHERE language.id = ".(int)$languageId." AND language_content.is_locked = 0 ORDER BY languageKey");
if (!$languageData)
{
    echo t("could_not_load_the_language_content", "Could not load language content.");
}
else
{
    // start output buffering
    coreFunctions::flushOutput();
	
	// 1KB of initial data, required by Webkit browsers
	echo "<span style='display: none;'>" . str_repeat("0", 1024) . "</span>";

    echo '<p>- Found '.COUNT($languageData).' items (which aren\'t locked). Translating to \''.$languageItem['language_code'].'\' ('.$languageItem['languageName'].')...</p>';

    // output results
    coreFunctions::flushOutput();

	// do the translation, ensuring no more than 100 per second
	$googleTranslate = new googleTranslate($languageItem['language_code']);
	$tracker = 1;
	foreach($languageData AS $languageDataItem)
	{
		$translation = $googleTranslate->translate($languageDataItem['defaultContent']);
		if ($translation !== false)
		{
			// update item within the database, also set as locked so this process can be run from where it finished if it fails
			$db->query('UPDATE language_content SET content='.$db->quote($translation).', is_locked = 1 WHERE id = '.$languageDataItem['id'].' AND is_locked = 0 LIMIT 1');
			
			// onscreen progress
			if($tracker % 50 == 0)
			{
				// output results
				echo '<p>- Completed '.$tracker.' translations...</p>';
				coreFunctions::flushOutput();
			}
			$tracker++;
		}
		else
		{
			die('<font style="color: red;">'.$googleTranslate->getError().'</font>');
		}
	}

    // output results
    coreFunctions::flushOutput();

    echo '<p style="color: green; font-weight:bold;">- Auto translation of '.COUNT($languageData).' items to \''.$languageItem['language_code'].'\' ('.$languageItem['languageName'].') complete.</p>';
}
?>

    </body>
</html>
