<?php

echo "hello";

$conn = new mysqli("localhost", "cfe", "cfe");
	$conn->query("INSERT INTO toto(t) VALUES('titi')");

