#!/usr/local/bin/php
<?php 
/*
	$Id: status_graph.php 369 2010-03-24 22:56:25Z awhite $
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

$pgtitle = array("Status", "Traffic graph");
require("guiconfig.inc");

$curif = "wan";
if ($_GET['if'])
	$curif = $_GET['if'];
	
if ($curif == "wan")
	$ifnum = get_real_wan_interface();
elseif ($curif == "SixXS")
	if (isset($wancfg['aiccu']['ayiya'])) {
		$ifnum = 'tun0';
	} else {
		$ifnum = 'gif0';
	}
else
	$ifnum = $config['interfaces'][$curif]['if'];
?>
<?php include("fbegin.inc"); ?>
<?php
$ifdescrs = array('wan' => 'WAN', 'lan' => 'LAN');
if (ipv6enabled() && ($config['interfaces']['wan']['ipaddr6'] == "aiccu")) {
	$ifdescrs['SixXS'] = 'SixXS';
}
for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
	$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
}
?>
<form name="form1" action="" method="get" style="padding-bottom: 10px; margin-bottom: 14px; border-bottom: 1px solid #999999">
Interface: 
<select name="if" class="formfld" onchange="document.form1.submit()">
<?php
foreach ($ifdescrs as $ifn => $ifd) {
	echo "<option value=\"$ifn\"";
	if ($ifn == $curif) echo " selected";
	echo ">" . htmlspecialchars($ifd) . "</option>\n";
}
?>
</select>
</form>
<div align="center">
<object data="graph.php?ifnum=<?=$ifnum;?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" type="image/svg+xml" width="550" height="275">
<param name="src" value="graph.php?ifnum=<?=$ifnum;?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" />
Your browser does not support the type SVG! You need to either use Firefox or download the Adobe SVG plugin.
</object>
</div>
<br><span class="red"><strong>Note:</strong></span> if you can't see the graph, you may need to download the most recent version of the <a href="http://www.mozilla.com/" target="_blank">Firefox</a> browser or install the <a href="http://www.adobe.com/svg/viewer/install/" target="_blank">Adobe SVG viewer</a>.
<?php include("fend.inc"); ?>
