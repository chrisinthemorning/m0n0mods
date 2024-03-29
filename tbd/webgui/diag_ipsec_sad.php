#!/usr/local/bin/php
<?php 
/*
	$Id: diag_ipsec_sad.php 238 2008-01-21 18:33:33Z mkasper $
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

$pgtitle = array("Diagnostics", "IPsec");

require("guiconfig.inc");
?>
<?php include("fbegin.inc"); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
    	$tabs = array('SAD' => 'diag_ipsec_sad.php',
            		  'SPD' => 'diag_ipsec_spd.php');
		dynamic_tab_menu($tabs);
?>
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
<?php

/* delete any SA? */
if ($_GET['act'] == "del") {
	$fd = @popen("/usr/local/sbin/setkey -c > /dev/null 2>&1", "w");
	if ($fd) {
		fwrite($fd, "delete {$_GET['src']} {$_GET['dst']} {$_GET['proto']} {$_GET['spi']} ;\n");
		pclose($fd);
		sleep(1);
	}
}

/* query SAD */
$fd = @popen("/usr/local/sbin/setkey -D", "r");
$sad = array();
if ($fd) {
	while (!feof($fd)) {
		$line = chop(fgets($fd));
		if (!$line)
			continue;
		if ($line == "No SAD entries.")
			break;
		if ($line[0] != "\t") {
			if (is_array($cursa))
				$sad[] = $cursa;
			$cursa = array();
			list($cursa['src'],$cursa['dst']) = explode(" ", $line);
			$i = 0;
		} else {
			$linea = explode(" ", trim($line));
			if ($i == 1) {
				$cursa['proto'] = $linea[0];
				$cursa['spi'] = substr($linea[2], strpos($linea[2], "x")+1, -1);
			} else if ($i == 2) {
				$cursa['ealgo'] = $linea[1];
			} else if ($i == 3) {
				$cursa['aalgo'] = $linea[1];
			}
		}
		$i++;
	}
	if (is_array($cursa) && count($cursa))
		$sad[] = $cursa;
	pclose($fd);
}
if (count($sad)):
?>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="inner content pane">
  <tr>
                <td nowrap class="listhdrr">Source</td>
                <td nowrap class="listhdrr">Destination</a></td>
                <td nowrap class="listhdrr">Protocol</td>
                <td nowrap class="listhdrr">SPI</td>
                <td nowrap class="listhdrr">Enc. alg.</td>
                <td nowrap class="listhdr">Auth. alg.</td>
                <td nowrap class="list"></td>
	</tr>
<?php
foreach ($sad as $sa): ?>
	<tr>
		<td class="listlr"><?=htmlspecialchars($sa['src']);?></td>
		<td class="listr"><?=htmlspecialchars($sa['dst']);?></td>
		<td class="listr"><?=htmlspecialchars(strtoupper($sa['proto']));?></td>
		<td class="listr"><?=htmlspecialchars($sa['spi']);?></td>
		<td class="listr"><?=htmlspecialchars($sa['ealgo']);?></td>
		<td class="listr"><?=htmlspecialchars($sa['aalgo']);?></td>
		<td class="list" nowrap>
		<?php
			$args = "src=" . rawurlencode($sa['src']);
			$args .= "&amp;dst=" . rawurlencode($sa['dst']);
			$args .= "&amp;proto=" . rawurlencode($sa['proto']);
			$args .= "&amp;spi=" . rawurlencode("0x" . $sa['spi']);
		?>
		  <a href="diag_ipsec_sad.php?act=del&amp;<?=$args;?>" onclick="return confirm('Do you really want to delete this security association?')"><img src="x.gif" title="delete SA" width="17" height="17" border="0" alt="delete SA"></a>
		</td>
				
	</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p><strong>No IPsec security associations.</strong></p>
<?php endif; ?>
</td></tr></table>
<?php include("fend.inc"); ?>
