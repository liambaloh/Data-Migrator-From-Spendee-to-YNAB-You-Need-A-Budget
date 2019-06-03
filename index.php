<?php

// Spendee export CSV format: 
// Date, Wallet, Category Type, Category Name, Amount, Currency, Note, Place, Address, First Name, Last Name
// YNAB Input CSV format:
// Date, Payee, Memo, Amount

$ynabHeaders = Array("Date", "Payee", "Memo", "Amount");
$spendeeHeaders = Array();
$spendeeTable = Array();

$metaData = Array();

$row = 1;
if (($handle = fopen("in.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);

        $spendeeRow = Array();

        if($row == 1){
            for ($c=0; $c < $num; $c++) {
                $spendeeHeaders[$c] = $data[$c];
            }
        }else{
            for ($c=0; $c < $num; $c++) {
                $spendeeRow[$spendeeHeaders[$c]] = $data[$c];
            }
        }

        $spendeeTable[$row] = $spendeeRow;
        $row++;
    }
    fclose($handle);
}

print "<table><tr>";
foreach($spendeeHeaders as $key){
    print "<th>$key</th>";
}
print "</tr>";

foreach($spendeeTable as $i => $spendeeRow){
    if($i == 1){
        continue;
    }

    $wallet = $spendeeRow["Wallet"];
    $currency = $spendeeRow["Currency"];

    if(!isset($metaData[$wallet])){
        $metaData[$wallet] = Array("Currencies" => Array());
    }

    if(array_search($currency, $metaData[$wallet]["Currencies"]) === false){
        $metaData[$wallet]["Currencies"][] = $currency;
    }
}

foreach($spendeeTable as $i => $spendeeRow){
    if($i == 1){
        continue;
    }
        
    print "<tr>";
    foreach($spendeeHeaders as $key){
        print "<td>".$spendeeRow[$key]."</td>";
    }
    print "</tr>";
}

print "</table>";


$ynabTable = Array();

foreach($spendeeTable as $i => $spendeeRow){
    if($i == 1){
        continue;
    }

    $wallet = $spendeeRow["Wallet"];

    $ynabRow = Array();
    $ynabRow["Date"] = $spendeeRow["Date"];
    $ynabRow["Memo"] = $spendeeRow["Category Name"] . " | " . $spendeeRow["Note"];
    $ynabRow["Payee"] = $spendeeRow["Place"] . " | " . $spendeeRow["Address"];
    $ynabRow["Amount"] = $spendeeRow["Amount"];

    $ynabTable[$wallet][] = $ynabRow;
}



foreach($ynabTable as $wallet => $ynabWalletTable){
    print "<h2>YNAB Data for Spendee wallet $wallet</h2>";
    print "<table><tr>";
    foreach($ynabHeaders as $key){
        print "<th>$key</th>";
    }
    print "</tr>";
    foreach($ynabWalletTable as $i => $ynabRow){
        
        print "<tr>";
        foreach($ynabHeaders as $key){
            print "<td>".$ynabRow[$key]."</td>";
        }
        print "</tr>";
    }
}

print "</table>";

print "<table><tr>";
print "<th>Wallet</th><th>Currencies</th>";
print "</tr>";
$oneCurrencyPerWallet = true;
foreach($metaData as $wallet => $metaRow){
    print "<tr>";
    print "<td>".$wallet."</td>";
    print "<td>".implode(", ", $metaRow["Currencies"])."</td>";
    if(count($metaRow["Currencies"]) != 1){
        $oneCurrencyPerWallet = false;
    }
    print "</tr>";
}
print "</table>";

if(!$oneCurrencyPerWallet){
    print "<p>Warning: More than one currency per wallet, YNAB does not support multiple currencies per budget.</p>";
}

if(!file_exists("out")){
    mkdir("out");
}

foreach($ynabTable as $wallet => $ynabWalletTable){
    $currency = $metaData[$wallet]["Currencies"][0];

    if(!file_exists("out/$currency")){
        mkdir("out/$currency");
    }

    $fp = fopen("out/$currency/$wallet.csv", 'w');
    fputcsv($fp, $ynabHeaders);
    foreach($ynabWalletTable as $i => $walletRow){
        fputcsv($fp, $walletRow);
    }
}

?>