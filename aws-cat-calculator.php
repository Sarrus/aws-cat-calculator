<?php

const EXIT_SUCCESS = 0;
const EXIT_FAILURE = 1;

$file = null;

foreach(getopt("f:h") as $option => $argument)
{
    switch($option)
    {
        case 'f':
            $file = $argument;
            break;

        case 'h':
            fprintf(STDERR, "Usage: php aws-cat-calculator.php [options]\r\n" .
                "  -f  Read CAT report from this file (required)\r\n" .
                "  -h  Display this help.\r\n");
            exit(EXIT_SUCCESS);
    }
}

if(!$file)
{
    fprintf(STDERR, "File is required.");
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

if(!$totalCostPosition || !$userCustomerPosition)
{
    fprintf(STDERR, "Missing required headers\r\n");
    exit(EXIT_FAILURE);
}

$totals = array();

while($line = fgetcsv($fileHandle))
{
    @$totals[$line[$userCustomerPosition]] += $line[$totalCostPosition];
}

print_r($totals);