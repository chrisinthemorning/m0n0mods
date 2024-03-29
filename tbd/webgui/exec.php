#!/usr/local/bin/php
<?php
/*
	$Id: exec.php 238 2008-01-21 18:33:33Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2006 technologEase (http://www.technologEase.com) and Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	
	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.
	
	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.
	
	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

/* omit no-cache headers because it confuses IE with file downloads */
$omit_nocacheheaders = true;
require("guiconfig.inc");

if (($_POST['submit'] == "Download") && file_exists($_POST['dlPath'])) {
	session_cache_limiter('public');
	$fd = fopen($_POST['dlPath'], "rb");
	header("Content-Type: application/octet-stream");
	header("Content-Length: " . filesize($_POST['dlPath']));
	header("Content-Disposition: attachment; filename=\"" . 
		trim(htmlentities(basename($_POST['dlPath']))) . "\"");
	
	fpassthru($fd);
	exit;
} else if (($_POST['submit'] == "Upload") && is_uploaded_file($_FILES['ulfile']['tmp_name'])) {
	move_uploaded_file($_FILES['ulfile']['tmp_name'], "/tmp/" . $_FILES['ulfile']['name']);
	$ulmsg = "Uploaded file to /tmp/" . htmlentities($_FILES['ulfile']['name']);
	unset($_POST['txtCommand']);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php

/*
	Exec+ v1.02-000 - Copyright 2001-2003, All rights reserved
	Created by technologEase (http://www.technologEase.com).
	
	(modified for m0n0wall by Manuel Kasper <mk@neon1.net>)
*/

// Function: is Blank
// Returns true or false depending on blankness of argument.

function isBlank( $arg ) { return ereg( "^\s*$", $arg ); }


// Function: Puts
// Put string, Ruby-style.

function puts( $arg ) { echo "$arg\n"; }


// "Constants".

$Version    = '';
$ScriptName = $HTTP_SERVER_VARS['SCRIPT_NAME'];
$Title      = 'm0n0wall: execute command';

// Get year.

$arrDT   = localtime();
$intYear = $arrDT[5] + 1900;

?>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title><?=$Title ?></title>
<script type="text/javascript">
<!--

   // Create recall buffer array (of encoded strings).

<?php

if (isBlank( $_POST['txtRecallBuffer'] )) {
   puts( "   var arrRecallBuffer = new Array;" );
} else {
   puts( "   var arrRecallBuffer = new Array(" );
   $arrBuffer = explode( "&", $_POST['txtRecallBuffer'] );
   for ($i=0; $i < (count( $arrBuffer ) - 1); $i++) puts( "      '" . $arrBuffer[$i] . "'," );
   puts( "      '" . $arrBuffer[count( $arrBuffer ) - 1] . "'" );
   puts( "   );" );
}

?>

   // Set pointer to end of recall buffer.
   var intRecallPtr = arrRecallBuffer.length;

   // Functions to extend String class.
   function str_encode() { return escape( this ) }
   function str_decode() { return unescape( this ) }
      
   // Extend string class to include encode() and decode() functions.
   String.prototype.encode = str_encode
   String.prototype.decode = str_decode

   // Function: is Blank
   // Returns boolean true or false if argument is blank.
   function isBlank( strArg ) { return strArg.match( /^\s*$/ ) }

   // Function: frmExecPlus onSubmit (event handler)
   // Builds the recall buffer from the command string on submit.
   function frmExecPlus_onSubmit( form ) {

      if (!isBlank(form.txtCommand.value)) {
		  // If this command is repeat of last command, then do not store command.
		  if (form.txtCommand.value.encode() == arrRecallBuffer[arrRecallBuffer.length-1]) { return true }
	
		  // Stuff encoded command string into the recall buffer.
		  if (isBlank(form.txtRecallBuffer.value))
			 form.txtRecallBuffer.value = form.txtCommand.value.encode();
		  else
			 form.txtRecallBuffer.value += '&' + form.txtCommand.value.encode();
	  }

      return true;
   }

   // Function: btnRecall onClick (event handler)
   // Recalls command buffer going either up or down.
   function btnRecall_onClick( form, n ) {

      // If nothing in recall buffer, then error.
      if (!arrRecallBuffer.length) {
         alert( 'Nothing to recall!' );
         form.txtCommand.focus();
         return;
      }

      // Increment recall buffer pointer in positive or negative direction
      // according to <n>.
      intRecallPtr += n;

      // Make sure the buffer stays circular.
      if (intRecallPtr < 0) { intRecallPtr = arrRecallBuffer.length - 1 }
      if (intRecallPtr > (arrRecallBuffer.length - 1)) { intRecallPtr = 0 }

      // Recall the command.
      form.txtCommand.value = arrRecallBuffer[intRecallPtr].decode();
   }

   // Function: Reset onClick (event handler)
   // Resets form on reset button click event.
   function Reset_onClick( form ) {

      // Reset recall buffer pointer.
      intRecallPtr = arrRecallBuffer.length;

      // Clear form (could have spaces in it) and return focus ready for cmd.
      form.txtCommand.value = '';
      form.txtCommand.focus();

      return true;
   }

   // hansmi, 2005-01-13
   function txtCommand_onKey(e) {
       if(!e) var e = window.event; // IE-Fix
       var code = (e.keyCode?e.keyCode:(e.which?e.which:0));
       if(!code) return;
       var f = document.getElementsByName('frmExecPlus')[0];
       if(!f) return;
       switch(code) {
       case 38: // up
           btnRecall_onClick(f, -1);
           break;
       case 40: // down
           btnRecall_onClick(f, 1);
           break;
       }
   }
//-->
</script>
<link href="gui.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--

input {
   font-family: courier new, courier;
   font-weight: normal;
   font-size: 9pt;
}

pre {
   border: 2px solid #435370;
   background: #F0F0F0;
   padding: 1em;
   font-family: courier new, courier;
   white-space: pre;
   line-height: 10pt;
   font-size: 10pt;
}

.label {
   font-family: tahoma, verdana, arial, helvetica;
   font-size: 11px;
   font-weight: bold;
}

.button {
   font-family: tahoma, verdana, arial, helvetica;
   font-weight: bold;
   font-size: 11px;
}

-->
</style>
</head>
<body<?php
if(!isBlank($_POST['txtCommand'])) {
    echo ' onload="document.forms[\'frmExecPlus\'].txtCommand.focus();"';
}
?>>
<p><span class="pgtitle"><?=$Title ?></span>
<?php if (isBlank($_POST['txtCommand'])): ?>
<p class="red"><strong>Note: this function is unsupported. Use it
on your own risk!</strong></p>
<?php endif; ?>
<?php if ($ulmsg) echo "<p><strong>" . $ulmsg . "</strong></p>\n"; ?>
<?php

if (!isBlank($_POST['txtCommand'])) {
   puts("<pre>");
   puts("\$ " . htmlspecialchars($_POST['txtCommand']));
   putenv("PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin");
   putenv("SCRIPT_FILENAME=" . strtok($_POST['txtCommand'], " "));	/* PHP scripts */
   $ph = popen($_POST['txtCommand'], "r" );
   while ($line = fgets($ph)) echo htmlspecialchars($line);
   pclose($ph);
   puts("</pre>");
}

?>

<form action="<?=$ScriptName ?>" method="POST" enctype="multipart/form-data" name="frmExecPlus" onSubmit="return frmExecPlus_onSubmit( this );">
  <table summary="content pane">
    <tr>
      <td class="label" align="right">Command:</td>
      <td class="type"><input name="txtCommand" type="text" size="80" value="" onkeypress="txtCommand_onKey(event);"></td>
    </tr>
    <tr>
      <td valign="top">&nbsp;</td>
      <td valign="top" class="label">
         <input type="hidden" name="txtRecallBuffer" value="<?=$_POST['txtRecallBuffer'] ?>">
         <input type="button" class="button" name="btnRecallPrev" value="<" onClick="btnRecall_onClick( this.form, -1 );">
         <input type="submit" class="button" value="Execute">
         <input type="button" class="button" name="btnRecallNext" value=">" onClick="btnRecall_onClick( this.form,  1 );">
         <input type="button"  class="button" value="Clear" onClick="return Reset_onClick( this.form );">
      </td>
    </tr>
    <tr>
      <td height="8"></td>
      <td></td>
    </tr>
    <tr>
      <td align="right">Download:</td>
      <td>
        <input name="dlPath" type="text" id="dlPath" size="50">
        <input name="submit" type="submit"  class="button" id="download" value="Download">
        </td>
    </tr>
    <tr>
      <td align="right">Upload:</td>
      <td valign="top" class="label">
<input name="ulfile" type="file" class="button" id="ulfile">
        <input name="submit" type="submit"  class="button" id="upload" value="Upload"></td>
    </tr>
  </table>
</form>
</body>
</html>
