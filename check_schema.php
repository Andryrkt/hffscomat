<?php
$conn = odbc_connect('IPS_HFFPROD_TEST', 'informix', 'k6UK19zaaAV10i');
if (!$conn) {
    die("ODBC Error: " . odbc_errormsg());
}

$query = "
SELECT c.colname, c.collength, c.coltype
FROM ir_prod108_test:syscolumns c
JOIN ir_prod108_test:systables t ON c.tabid = t.tabid
WHERE t.tabname = 'devis_soumis_a_validation'
";

$stmt = odbc_exec($conn, $query);
while ($row = odbc_fetch_array($stmt)) {
    echo $row['colname'] . " - " . $row['collength'] . "\n";
}
