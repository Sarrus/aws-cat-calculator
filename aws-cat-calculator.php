<?php

const EXIT_SUCCESS = 0;
const EXIT_FAILURE = 1;

$file = null;
$filterBuyInvoiceNo = null;
$localCurrencyTotal = null;
$localCurrencySymbol = null;

foreach(getopt("c:f:hi:s:") as $option => $argument)
{
    switch($option)
    {
        case 'c':
            $localCurrencyTotal = (float)$argument;
            break;

        case 'f':
            $file = $argument;
            break;

        case 'h':
            fprintf(STDERR, "Usage: php aws-cat-calculator.php [options]\r\n" .
                "  -c  Convert to local currency (must specify the bill total in local currency)" .
                "  -f  Read CAT report from this file (required)\r\n" .
                "  -h  Display this help\r\n" .
                "  -i  Filter by this invoice number\r\n" .
                "  -s  Local currency symbol");
            exit(EXIT_SUCCESS);

        case 'i':
            $filterBuyInvoiceNo = $argument;
            break;

        case 's':
            $localCurrencySymbol = $argument;
            break;
    }
}

if(!$filterBuyInvoiceNo)
{
    fprintf(STDERR, "Warning: Invoice number (option -i) not specified. " .
        "Output may include non-usage charges such as EC2 reservations.\r\n");
}

if(!$file)
{
    fprintf(STDERR, "File is required.\r\n");
    exit(EXIT_FAILURE);
}

$fileHandle = fopen($file, 'r');
$topLine = fgets($fileHandle);

if($topLine != "\"Don't see your tags in the report? New tags are excluded by default - go to https://console.aws.amazon.com/billing/home#/preferences/tags to update your cost allocation keys.\"\n")
{
    fseek($fileHandle, 0);
}

$headers = fgetcsv($fileHandle);
$totalCostPosition = array_search("TotalCost", $headers);
$userCustomerPosition = array_search("user:Customer", $headers);
$invoiceIdPosition = array_search("InvoiceID", $headers);
$recordTypePosition = array_search("RecordType", $headers);

if(!is_int($totalCostPosition)
    || !is_int($userCustomerPosition)
    || !is_int($recordTypePosition)
    || ($filterBuyInvoiceNo && !is_int($invoiceIdPosition))
)
{
    fprintf(STDERR, "Missing required headers\r\n");
    exit(EXIT_FAILURE);
}

$totals = array();

while($line = fgetcsv($fileHandle))
{
    if($line[$recordTypePosition] == "PayerLineItem" &&
        (!$filterBuyInvoiceNo || ($line[$invoiceIdPosition] == $filterBuyInvoiceNo)))
    {
        @$totals[$line[$userCustomerPosition]] += $line[$totalCostPosition];
    }
}

$grandTotal = array_sum($totals);

foreach($totals as $customer => $total)
{
    if($customer == '')
    {
        printf("Total internal usage: ");
    }
    else
    {
        printf("Total usage for %s: ", $customer);
    }

    $proportionOfTotal = $total / $grandTotal;

    printf("$%.2f, Percentage of total: %.0f%% ", $total, $proportionOfTotal * 100);

    if($localCurrencyTotal)
    {
        printf("In local currency: ");
        if($localCurrencySymbol)
        {
            printf("%s", $localCurrencySymbol);
        }
        printf("%.2f", $localCurrencyTotal * $proportionOfTotal);
    }

    printf("\r\n");
}