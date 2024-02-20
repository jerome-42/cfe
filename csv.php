<?php

function parseCSV($handle, $firstLineIsHeaders) {
    $lines = [];
    $lineNo = 0;
    $totalDuration = 0;
    $errors = [];
    $now = new DateTime();
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if ($firstLineIsHeaders == false || $lineNo++ > 0) { // on ne prend pas le header
            try {
                $duration = intval($data[3]) * 60 + intval($data[4]);
                $date = parseDateDDMMAAAA($data[0]);
                $d = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
                $date = new DateTime();
                $date->setTimestamp($d);
                if ($date > $now)
                    throw new Exception("La date est postérieure à aujourd'hui");
                $dateString = $date->format('d/m/Y');
                $totalDuration += $duration;
                $lines[] = [ 'date' => $dateString, 'd' => $date,
                             'type' => $data[1], 'beneficiary' => $data[2],
                             'duration' => $duration, 'details' => $data[5] ];
            }
            catch (Exception $e) {
                $errors[] = "Ligne ".$lineNo.": ".$e->getMessage();
            }
        }
    }
    return [ $lines, $errors, $totalDuration ];
}
