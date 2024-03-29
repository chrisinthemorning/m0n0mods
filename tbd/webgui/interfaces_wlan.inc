<?php 
/*
	$Id: interfaces_wlan.inc 216 2007-08-01 12:17:59Z mkasper $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2007 Manuel Kasper <mk@neon1.net>.
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

$wlstandards = wireless_get_standards($optcfg['if']);
$wlchannels = wireless_get_channellist($optcfg['if']);

function wireless_get_standards($if) {
	$standards = array();
	
	$fd = popen("/sbin/ifconfig -m $if", "r");
	
	if ($fd) {
		while (!feof($fd)) {
			$line = trim(fgets($fd));
			
			if (preg_match("/media \S+ mode (11\S+)/", $line, $matches)) {
				$standards[] = $matches[1];
			}
		}
	}
	
	return array_unique($standards);
}

function wireless_get_channellist($if) {
	
	$chanlist = array();
	
	$fd = popen("/sbin/ifconfig $if list chan", "r");
	if ($fd) {
		while (!feof($fd)) {
			$line = trim(fgets($fd));
			
			/* could have two channels on this line */
			$chans = explode("Channel", $line);
			
			foreach ($chans as $chan) {
				if (preg_match("/(\d+)\s+:\s+(\d+)\s+Mhz\s+(.+)/", $chan, $matches)) {
					$chaninfo = array();
					$chaninfo['chan'] = $matches[1];
					$chaninfo['freq'] = $matches[2];
					$chaninfo['mode'] = trim($matches[3]);
					
					$chanlist[$chaninfo['chan']] = $chaninfo;
				}
			}
		}
		pclose($fd);
	}
	
	ksort($chanlist, SORT_NUMERIC);
	
	return $chanlist;
}

function wireless_config_init() {
	global $optcfg, $pconfig;
	
	$pconfig['standard'] = $optcfg['wireless']['standard'];
	$pconfig['mode'] = $optcfg['wireless']['mode'];
	$pconfig['ssid'] = $optcfg['wireless']['ssid'];
	$pconfig['channel'] = $optcfg['wireless']['channel'];
	$pconfig['wep_enable'] = isset($optcfg['wireless']['wep']['enable']);
	$pconfig['hidessid'] = isset($optcfg['wireless']['hidessid']);
	
	$pconfig['wpamode'] = $optcfg['wireless']['wpa']['mode'];
	$pconfig['wpaversion'] = $optcfg['wireless']['wpa']['version'];
	$pconfig['wpacipher'] = $optcfg['wireless']['wpa']['cipher'];
	$pconfig['wpapsk'] = $optcfg['wireless']['wpa']['psk'];
	$pconfig['radiusip'] = $optcfg['wireless']['wpa']['radius']['server'];
	$pconfig['radiusauthport'] = $optcfg['wireless']['wpa']['radius']['authport'];
	$pconfig['radiusacctport'] = $optcfg['wireless']['wpa']['radius']['acctport'];
	$pconfig['radiussecret'] = $optcfg['wireless']['wpa']['radius']['secret'];
	
	if (is_array($optcfg['wireless']['wep']['key'])) {
		$i = 1;
		foreach ($optcfg['wireless']['wep']['key'] as $wepkey) {
			$pconfig['key' . $i] = $wepkey['value'];
			if (isset($wepkey['txkey']))
				$pconfig['txkey'] = $i;
			$i++;
		}
		if (!isset($wepkey['txkey']))
			$pconfig['txkey'] = 1;
	}
}

function wireless_config_post() {
	global $optcfg, $pconfig, $wlchannels;

	unset($input_errors);

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = "mode ssid channel";
		$reqdfieldsn = "Mode,SSID,Channel";
		
		if ($_POST['wpamode'] != "none") {
			$reqdfields .= " wpaversion wpacipher";
			$reqdfieldsn .= ",WPA version,WPA cipher";
			
			if ($_POST['wpamode'] == "psk") {
				$reqdfields .= " wpapsk";
				$reqdfieldsn .= ",WPA PSK";
			} else if ($_POST['wpamode'] == "enterprise") {
				$reqdfields .= " radiusip radiussecret";
				$reqdfieldsn .= ",RADIUS IP,RADIUS Shared secret";
			}
		}
		
		do_input_validation($_POST, explode(" ", $reqdfields), explode(",", $reqdfieldsn), &$input_errors);
		
		if ($_POST['radiusip'] && !is_ipaddr($_POST['radiusip']))
			$input_errors[] = "A valid RADIUS IP address must be specified.";
		if ($_POST['radiusauthport'] && !is_port($_POST['radiusauthport']))
			$input_errors[] = "A valid RADIUS authentication port number must be specified.";
		if ($_POST['radiusacctport'] && !is_port($_POST['radiusacctport']))
			$input_errors[] = "A valid RADIUS accounting port number must be specified.";
	
		if ($_POST['wpapsk'] && !(strlen($_POST['wpapsk']) >= 8 && strlen($_POST['wpapsk']) <= 63))
			$input_errors[] = "The WPA PSK must be between 8 and 63 characters long.";
	
		if (!$input_errors) {
			/* bridge check (hostap only!) */
			if ($pconfig['bridge'] && ($pconfig['mode'] != "hostap"))
				$input_errors[] = "Bridging a wireless interface is only possible in hostap mode.";
				
			/* if an 11a channel is selected, the mode must be 11a too, and vice versa */
			$is_chan_11a = (strpos($wlchannels[$_POST['channel']]['mode'], "11a") !== false);
			$is_std_11a = ($_POST['standard'] == "11a");
			if ($is_chan_11a && !$is_std_11a)
				$input_errors[] = "802.11a channels can only be selected if the standard is set to 802.11a too.";
			else if (!$is_chan_11a && $is_std_11a)
				$input_errors[] = "802.11a can only be selected if an 802.11a channel is selected too.";
			
			/* currently, WPA is only supported in AP (hostap) mode */
			if ($_POST['wpamode'] != "none" && $pconfig['mode'] != "hostap")
				$input_errors[] = "WPA is only supported in hostap mode.";
		}
	}

	if (!$input_errors) {
	
		$optcfg['wireless']['standard'] = $_POST['standard'];
		$optcfg['wireless']['mode'] = $_POST['mode'];
		$optcfg['wireless']['ssid'] = $_POST['ssid'];
		$optcfg['wireless']['channel'] = $_POST['channel'];
		$optcfg['wireless']['wep']['enable'] = $_POST['wep_enable'] ? true : false;
		$optcfg['wireless']['hidessid'] = $_POST['hidessid'] ? true : false;
		
		$optcfg['wireless']['wep']['key'] = array();
		for ($i = 1; $i <= 4; $i++) {
			if ($_POST['key' . $i]) {
				$newkey = array();
				$newkey['value'] = $_POST['key' . $i];
				if ($_POST['txkey'] == $i)
					$newkey['txkey'] = true;
				$optcfg['wireless']['wep']['key'][] = $newkey;
			}
		}
		
		$optcfg['wireless']['wpa'] = array();
		$optcfg['wireless']['wpa']['mode'] = $_POST['wpamode'];
		$optcfg['wireless']['wpa']['version'] = $_POST['wpaversion'];
		$optcfg['wireless']['wpa']['cipher'] = $_POST['wpacipher'];
		$optcfg['wireless']['wpa']['psk'] = $_POST['wpapsk'];
		$optcfg['wireless']['wpa']['radius'] = array();
		$optcfg['wireless']['wpa']['radius']['server'] = $_POST['radiusip'];
		$optcfg['wireless']['wpa']['radius']['authport'] = $_POST['radiusauthport'];
		$optcfg['wireless']['wpa']['radius']['acctport'] = $_POST['radiusacctport'];
		$optcfg['wireless']['wpa']['radius']['secret'] = $_POST['radiussecret'];
	}
	
	return $input_errors;
}

function wireless_config_print() {
	global $g, $optcfg, $pconfig, $wlchannels, $wlstandards;
?>
                <tr> 
                  <td colspan="2" valign="top" height="16"></td>
				</tr>
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">Wireless configuration</td>
				</tr>
                <tr>
                  <td valign="top" class="vncellreq">Standard</td>
                  <td class="vtable"><select name="standard" class="formfld" id="standard">
                      <?php
					  foreach ($wlstandards as $sn): ?>
                      <option value="<?=$sn;?>" <?php if ($sn == $pconfig['standard']) echo "selected";?>>
                      <?="802.$sn";?>
                      </option>
                      <?php endforeach; ?>
                    </select></td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">Mode</td>
                  <td class="vtable"><select name="mode" class="formfld" id="mode" onChange="wlan_enable_change(false)">
                      <?php 
						$opts = array();
						if (preg_match($g['wireless_hostap_regex'], $optcfg['if']))
							$opts[] = "hostap";
						$opts[] = "BSS";
						$opts[] = "IBSS";
				foreach ($opts as $opt): ?>
                      <option <?php if ($opt == $pconfig['mode']) echo "selected";?>> 
                      <?=htmlspecialchars($opt);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    Note: To create an access-point, choose &quot;hostap&quot; mode.
                    IBSS mode is sometimes also called &quot;ad-hoc&quot; 
                    mode; BSS mode is also known as &quot;infrastructure&quot; mode.</td>
				</tr>
                <tr> 
                  <td valign="top" class="vncellreq">SSID</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="ssid" type="text" class="formfld" id="ssid" size="20" value="<?=htmlspecialchars($pconfig['ssid']);?>">
                    <br><br><input type="checkbox" name="hidessid" id="hidessid" value="1" <?php if ($pconfig['hidessid']) echo "checked";?>><strong>Hide SSID</strong><br>
                    If this option is selected, the SSID will not be broadcast in hostap mode, and only clients that
                    know the exact SSID will be able to connect. Note that this option should never be used
                    as a substitute for proper security/encryption settings.
                  </td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">Channel</td>
                  <td class="vtable"><select name="channel" class="formfld" id="channel">
                      <option <?php if ($pconfig['channel'] == 0) echo "selected";?> value="0">Auto</option>
                      <?php
					  foreach ($wlchannels as $channel => $chaninfo):
					    if ($chaninfo['mode'] == "11g")
					    	$mode = "11b/g";
					    else
					    	$mode = $chaninfo['mode'];
					    
					  	$chandescr = "{$chaninfo['chan']} ({$chaninfo['freq']} MHz, {$mode})";
					  ?>
                      <option <?php if ($channel == $pconfig['channel']) echo "selected";?> value="<?=$channel;?>">
                      <?=$chandescr;?>
                      </option>
                      <?php endforeach; ?>
                    </select></td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">WPA</td>
                  <td class="vtable">
                    <table width="100%" border="0" cellpadding="6" cellspacing="0">
						<tr> 
							<td colspan="2" valign="top" class="optsect_t2">WPA settings</td>
						</tr>
						<tr>
							<td class="vncell" valign="top">Mode</td>
							<td class="vtable">
								<select name="wpamode" id="wpamode" onChange="wlan_enable_change(false)">
									<option value="none" <?php if (!$pconfig['wpamode'] || $pconfig['wpamode'] == "none") echo "selected";?>>none</option>
									<option value="psk" <?php if ($pconfig['wpamode'] == "psk") echo "selected";?>>PSK</option>
									<option value="enterprise" <?php if ($pconfig['wpamode'] == "enterprise") echo "selected";?>>Enterprise</option>
								</select>
							</td>
						</tr>
						<tr>
							<td class="vncell" valign="top">Version</td>
							<td class="vtable">
								<select name="wpaversion" id="wpaversion">
									<option value="1" <?php if ($pconfig['wpaversion'] == "1") echo "selected";?>>WPA only</option>
									<option value="2" <?php if ($pconfig['wpaversion'] == "2") echo "selected";?>>WPA2 only</option>
									<option value="3" <?php if ($pconfig['wpaversion'] == "3") echo "selected";?>>WPA + WPA2</option>
								</select><br>
								In most cases, you should select &quot;WPA + WPA2&quot; here.
							</td>
						</tr>
						<tr>
							<td class="vncell" valign="top">Cipher</td>
							<td class="vtable">
								<select name="wpacipher" id="wpacipher">
									<option value="tkip" <?php if ($pconfig['wpacipher'] == "tkip") echo "selected";?>>TKIP</option>
									<option value="ccmp" <?php if ($pconfig['wpacipher'] == "ccmp") echo "selected";?>>AES/CCMP</option>
									<option value="both" <?php if ($pconfig['wpacipher'] == "both") echo "selected";?>>TKIP + AES/CCMP</option>
								</select><br>
								AES/CCMP provides better security than TKIP, but TKIP is more compatible with older hardware.
							</td>
						</tr>
						<tr>
							<td class="vncell" valign="top">PSK</td>
							<td class="vtable">
								<input name="wpapsk" type="text" class="formfld" id="wpapsk" size="30" value="<?=htmlspecialchars($pconfig['wpapsk']);?>"><br>
									Enter the passphrase that will be used in WPA-PSK mode. This must be between 8 and 63 characters long.
							</td>
						</tr>
						<tr> 
						  <td colspan="2" class="list" height="12"></td>
						</tr>
						<tr> 
							<td colspan="2" valign="top" class="optsect_t2">RADIUS server</td>
						</tr>
						<tr>
							<td class="vncell" valign="top">IP address</td>
							<td class="vtable"><input name="radiusip" type="text" class="formfld" id="radiusip" size="20" value="<?=htmlspecialchars($pconfig['radiusip']);?>"><br>
							Enter the IP address of the RADIUS server that will be used in WPA-Enterprise mode.</td>
						</tr>
						<tr>
							<td class="vncell" valign="top">Authentication port</td>
							<td class="vtable"><input name="radiusauthport" type="text" class="formfld" id="radiusauthport" size="5" value="<?=htmlspecialchars($pconfig['radiusauthport']);?>"><br>
							 Leave this field blank to use the default port (1812).</td>
						</tr>
						<tr>
							<td class="vncell" valign="top">Accounting port</td>
							<td class="vtable"><input name="radiusacctport" type="text" class="formfld" id="radiusacctport" size="5" value="<?=htmlspecialchars($pconfig['radiusacctport']);?>"><br>
							 Leave this field blank to use the default port (1813).</td>
						</tr>
						<tr>
							<td class="vncell" valign="top">Shared secret&nbsp;&nbsp;</td>
							<td class="vtable"><input name="radiussecret" type="text" class="formfld" id="radiussecret" size="16" value="<?=htmlspecialchars($pconfig['radiussecret']);?>"></td>
						</tr>
					</table>
                  </td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">WEP</td>
                  <td class="vtable"> <input name="wep_enable" type="checkbox" id="wep_enable" value="yes" <?php if ($pconfig['wep_enable']) echo "checked"; ?>> 
                    <strong>Enable WEP</strong>
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr> 
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;TX key&nbsp;</td>
                      </tr>
                      <tr> 
                        <td>Key 1:&nbsp;&nbsp;</td>
                        <td> <input name="key1" type="text" class="formfld" id="key1" size="30" value="<?=htmlspecialchars($pconfig['key1']);?>"></td>
                        <td align="center"> <input name="txkey" type="radio" value="1" <?php if ($pconfig['txkey'] == 1) echo "checked";?>> 
                        </td>
                      </tr>
                      <tr> 
                        <td>Key 2:&nbsp;&nbsp;</td>
                        <td> <input name="key2" type="text" class="formfld" id="key2" size="30" value="<?=htmlspecialchars($pconfig['key2']);?>"></td>
                        <td align="center"> <input name="txkey" type="radio" value="2" <?php if ($pconfig['txkey'] == 2) echo "checked";?>></td>
                      </tr>
                      <tr> 
                        <td>Key 3:&nbsp;&nbsp;</td>
                        <td> <input name="key3" type="text" class="formfld" id="key3" size="30" value="<?=htmlspecialchars($pconfig['key3']);?>"></td>
                        <td align="center"> <input name="txkey" type="radio" value="3" <?php if ($pconfig['txkey'] == 3) echo "checked";?>></td>
                      </tr>
                      <tr> 
                        <td>Key 4:&nbsp;&nbsp;</td>
                        <td> <input name="key4" type="text" class="formfld" id="key4" size="30" value="<?=htmlspecialchars($pconfig['key4']);?>"></td>
                        <td align="center"> <input name="txkey" type="radio" value="4" <?php if ($pconfig['txkey'] == 4) echo "checked";?>></td>
                      </tr>
                    </table>
                    <br>
                    40 (64) bit keys may be entered as 5 ASCII characters or 10 
                    hex digits preceded by '0x'.<br>
                    104 (128) bit keys may be entered as 13 ASCII characters or 
                    26 hex digits preceded by '0x'.</td>
                </tr>
<?php } ?>
