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

$pgtitle = array("Services", "DNS forwarder", "Edit host");
require("guiconfig.inc");

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "domain ip");
	$reqdfieldsn = explode(",", "Domain,IP address");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if (($_POST['host'] && (!is_hostname($_POST['host'])) && ($_POST['host'] != '*') )) {
		$input_errors[] = "A valid host must be specified.";
	}
	if (($_POST['domain'] && !is_domain($_POST['domain']))) {
		$input_errors[] = "A valid domain must be specified.";
	}
	if (($_POST['ip'] && !is_ipaddr4or6($_POST['ip']))) {
			$input_errors[] = "A valid IP address must be specified.";
	}

	/* check for overlaps */
	foreach ($a_hosts as $hostent) {
		if (isset($id) && ($a_hosts[$id]) && ($a_hosts[$id] === $hostent))
			continue;

		if (($hostent['host'] == $_POST['host']) && ($hostent['domain'] == $_POST['domain'])) {
			$input_errors[] = "This host/domain already exists.";
			break;
		}
	}

	if (!$input_errors) {
		$hostent = array();
		$hostent['host'] = $_POST['host'];
		$hostent['domain'] = $_POST['domain'];
		$hostent['ip'] = $_POST['ip'];
		$hostent['descr'] = $_POST['descr'];

		if (isset($id) && $a_hosts[$id])
			$a_hosts[$id] = $hostent;
		else
			$a_hosts[] = $hostent;
		
		touch($d_ntpserverdirty_path);
		
		write_config();
		
		header("Location: services_dnsmasq.php");
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
                    </select><br>
					Type</td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Target</td>
                  <td width="78%" class="vtable"> 
                    <input name="host" type="text" class="formfld" id="host" size="40" value="<?=htmlspecialchars($pconfig['host']);?>">
                    <br> <span class="vexpl">IP address or serial port.
                   <br>
                    e.g. <em>/dev/ttyU0</em></span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncellreq">Reference ID</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="domain" type="text" class="formfld" id="domain" size="40" value="<?=htmlspecialchars($pconfig['domain']);?>">
                    <br> <span class="vexpl">This is the RefID that peers will see<br>
                    e.g. <em>GPS0</em></span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncellreq">MaxPoll</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="ip" type="text" class="formfld" id="ip" size="4" value="<?=htmlspecialchars($pconfig['ip']);?>">
                    <br> <span class="vexpl">IP address of the host<br>
                    e.g. <em>192.168.100.100</em></span></td>
                </tr>

				<tr>
                  <td width="22%" valign="top" class="vncell">MinPoll</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="4" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Offset</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="4" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">Burst</td>
                  <td width="78%" class="vtable"> 
                  <input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">iBurst</td>
                  <td width="78%" class="vtable"> 
                   <input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_hosts[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
