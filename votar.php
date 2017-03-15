<?php

	/************************************************************************************/
	/**/ function __autoload ($name) { include ("classes/" . $name . ".class.php"); } /**/
	/**/ $DB = new DataConn ();                                                       /**/
	/************************************************************************************/

	$addr = $_SERVER["REMOTE_ADDR"];

	if (count ($DB->table (RECORDS)->select ("record_id", "voter_id='".$addr."'")) != 0)
	{
		header ("location: votado.php");
		exit;
	}

	foreach ($_POST["id"] as $id)
	{
		$DB->execNonQuery ("UPDATE " . ELEMENTS . " SET votos = votos + 1 WHERE elem_id=" . $id);
		$DB->table (RECORDS)->insert (array (null, $addr, $id));
	}

	header ("location: gracias.php");

?>
