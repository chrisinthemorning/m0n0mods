#!/usr/local/bin/php
<?php
/*
	$Id: services_dnsmasq.php 366 2010-03-06 11:28:49Z mkasper $
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

$pgtitle = array("Services", "NTP Server");
require("guiconfig.inc");
global $config;
include("fbegin.inc"); 

if (!is_array($config['ntpserver']['sources'])) {
	$config['ntpserver']['sources'] = array();
}

$source_maps = &$config['ntpserver']['sources'];

if ($_POST) {
	$config['ntpserver']['enable'] = $_POST['enable'] ? true : false;
	write_config();
	system_ntp_configure(); 
}
$pconfig = $config['ntpserver'];
$pconfig['enable'] = isset($config['ntpserver']['enable']);

?>
<form action="services_ntp.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_ntpserverdirty_path)): ?><p>
<?php print_info_box_np("The NTP server configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
			  <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
					<tr> 
					  <td colspan="2" valign="top" class="optsect_t">
					  <table border="0" cellspacing="0" cellpadding="0" width="100%" summary="checkbox pane">
					  <tr><td class="optsect_s"><strong>Enable NTP server</strong></td>
					  <td align="right" class="optsect_s"><input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)"> <strong>Enable</strong></td></tr>
					  </table></td>
					</tr>
              </table>
				<br>
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="mac=source widget">
			  		<tr> 
					  <td colspan="8" valign="top" class="optsect_t">
					  <table border="0" cellspacing="0" cellpadding="0" width="100%" summary="checkbox pane">
					  <tr><td class="optsect_s"><strong>Sources</strong></td></tr>
					  </table></td>
					</tr>
                <tr>
                  <td width="10%" class="listhdrr">Type</td>
				  <td width="25%" class="listhdrr">Target</td>
                  <td width="10%" class="listhdrr">Refid</td>
				  <td width="10%" class="listhdrr">MaxPoll</td>
				  <td width="10%" class="listhdrr">MinPoll</td>
				  <td width="10%" class="listhdrr">Offset</td>
				  <td width="5%" class="listhdrr">Burst</td>
                  <td width="5%" class="listhdrr">iBurst</td>
				  <td width="10%" class="listhdr">Stratum</td>
                  <td width="5%" class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($source_maps as $sourceent): ?>
                <tr>
                  <td class="listlr">
                    <?=htmlspecialchars($sourceent['type']);?>
                  </td>
	             <td class="listlr">
                    <?=htmlspecialchars($sourceent['target']);?>
                  </td>
                  <td class="listr">
                    <?=htmlspecialchars($sourceent['refid']);?>&nbsp;
                  </td>
				    <td class="listr">
                    <?=htmlspecialchars($sourceent['maxpoll']);?>&nbsp;
                  </td>
				    <td class="listr">
                    <?=htmlspecialchars($sourceent['minpoll']);?>&nbsp;
                  </td>
				    <td class="listr">
                    <?=htmlspecialchars($sourceent['offset']);?>&nbsp;
                  </td>
				    <td class="listr">
					<input name="burst" type="checkbox"  <?php if ($sourceent['burst']) echo "checked"; ?> >
                  </td>
                  <td class="listr">
                    <input name="iburst" type="checkbox" <?php if ($sourceent['iburst']) echo "checked"; ?> >
                  </td>
					<td class="listr">
                    <?=htmlspecialchars($sourceent['stratum']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="services_ntp_edit.php?id=<?=$i;?>"><img src="e.gif" title="edit source" width="17" height="17" border="0" alt="edit source"></a>
                     &nbsp;<a href="services_ntp.php?act=del&amp;id=<?=$i;?>" onclick="return confirm('Do you really want to delete this source?')"><img src="x.gif" title="delete source" width="17" height="17" border="0" alt="delete source"></a></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="9"></td>
                  <td class="list"> <a href="services_ntp_edit.php?"><img src="plus.gif" title="add source" width="17" height="17" border="0" alt="add source"></a></td>
				</tr>
				<tr> 
					<td> <input name="submit" type="submit" class="formbtn" value="Save"> 
					</td>
					</tr>
              </table>
            </form>
<?php include("fend.inc"); ?>
