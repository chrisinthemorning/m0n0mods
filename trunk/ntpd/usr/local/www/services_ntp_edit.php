#!/usr/local/bin/php
<?php 
/*
	$Id: services_dnsmasq_edit.php 369 2010-03-24 22:56:25Z awhite $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2005 Bob Zoller <bob@kludgebox.com> and Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array("Services", "NTP Server", "Edit host");
require("guiconfig.inc");

if (!is_array($config['ntpserver']['sources'])) {
	$config['ntpserver']['sources'] = array();
}

$source_maps = &$config['ntpserver']['sources'];
$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];
	
if (isset($id) && $source_maps[$id]) {
		$pconfig['target'] = $ntpsource[$id]['target'];
		$pconfig['refid'] = $ntpsource[$id]['refid'];
		$pconfig['maxpoll'] = $ntpsource[$id]['maxpoll'];
		$pconfig['minpoll'] = $ntpsource[$id]['minpoll'];
		$pconfig['offset'] = $ntpsource[$id]['offset'];
		$pconfig['burst'] = $ntpsource[$id]['burst'];
		$pconfig['iburst'] = $ntpsource[$id]['iburst'];
		$pconfig['stratum'] = $ntpsource[$id]['stratum'];
}

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfieldsn = explode(",", "Type,Target");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['domain'] && !is_domain($_POST['domain']))) {
		$input_errors[] = "A valid domain must be specified.";
	}

	/* check for overlaps */


	if (!$input_errors) {
		$ntpsource = array();
		$ntpsource['type'] = $_POST['type'];
		$ntpsource['target'] = $_POST['target'];
		$ntpsource['refid'] = $_POST['refid'];
		$ntpsource['maxpoll'] = $_POST['maxpoll'];
		$ntpsource['minpoll'] = $_POST['minpoll'];
		$ntpsource['offset'] = $_POST['offset'];
		$ntpsource['burst'] = $_POST['burst'] ? true : false;
		$ntpsource['iburst'] = $_POST['iburst'] ? true : false;
		$ntpsource['stratum'] = $_POST['stratum'];
		if (isset($id) && $source_maps[$id])
			$source_maps[$id] = $ntpsource;
		else
			$source_maps[] = $ntpsource;
		
		write_config();
		
		header("Location: services_ntp.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="services_ntp_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
				<tr> 
                  <td valign="top" class="vncellreq">Type</td>
                  <td class="vtable"> 
                    <select name="type" class="formfld" id="type" onchange="enable_change(false)">
                      <?php $opts = array('ipaddr' => 'IP address', 'gps' => 'GPS', 'pps' => 'PPS', 'local' => 'Local');
						foreach ($opts as $optn => $optd) {
							echo "<option value=\"$optn\"";
							if ($optn == $pconfig['type']) echo "selected";
							echo ">$optd</option>\n";
						}
						?>
                    </select></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Target</td>
                  <td width="78%" class="vtable"> 
                    <input name="target" type="text" class="formfld" id="target" size="40" value="<?=htmlspecialchars($pconfig['host']);?>">
                    <br> <span class="vexpl">IP address or device port.
                   <br>
                    e.g. <em>/dev/ttyU0</em></span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncellreq">Reference ID</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="refid" type="text" class="formfld" id="refid" size="40" value="<?=htmlspecialchars($pconfig['domain']);?>">
                    <br> <span class="vexpl">This is the RefID that peers will see<br>
                    e.g. <em>GPS0</em></span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncellreq">MaxPoll</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="maxpoll" type="text" class="formfld" id="maxpoll" size="4" value="<?=htmlspecialchars($pconfig['maxpoll']);?>">
                    <br> <span class="vexpl">The maxpoll number specifies the maximum poll interval allowed by any peer of the Internet system.
					The maximum poll interval is calculated, in seconds, as 2 to the power of maxpoll value. The default value of maxpoll is 10,
					therefore the corresponding poll interval is ~17 minutes.<br>
                    e.g. <em>192.168.100.100</em></span></td>
                </tr>

				<tr>
                  <td width="22%" valign="top" class="vncell">MinPoll</td>
                  <td width="78%" class="vtable"> 
                    <input name="minpoll" type="text" class="formfld" id="minpoll" size="4" value="<?=htmlspecialchars($pconfig['minpoll']);?>">
                    <br> <span class="vexpl">The minpoll number specifies the minimum poll interval allowed by any peer of the Internet system.
					The minimum poll interval is calculated, in seconds, as 2 to the power of minpoll value.
					The default value of minpoll is 6, i.e. the corresponding poll interval is 64 seconds.</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Offset</td>
                  <td width="78%" class="vtable"> 
                    <input name="offset" type="text" class="formfld" id="offset" size="4" value="<?=htmlspecialchars($pconfig['offset']);?>">
                    <br> <span class="vexpl">Adjustment time for this source in ms.</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Burst</td>
                  <td width="78%" class="vtable"> 
                  <input name="burst" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
                    <br> <span class="vexpl">When a server is Reachable send a burst of eight packets during initial synchronization acquisition
					instead of the single packet that is normally sent.	The packet spacing is two seconds.</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">iBurst</td>
                  <td width="78%" class="vtable"> 
                   <input name="iburst" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
                    <br> <span class="vexpl">When a server is Uneachable send a burst of eight packets during initial synchronization acquisition
					instead of the single packet that is normally sent.	The packet spacing is two seconds.</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Stratum</td>
                  <td width="78%" class="vtable"> 
                    <input name="stratum" type="text" class="formfld" id="stratum" size="4" value="<?=htmlspecialchars($pconfig['offset']);?>">
                    <br> <span class="vexpl">Stratum</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $source_maps[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
