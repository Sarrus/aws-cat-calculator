<?php

foreach(getopt("h") as $option => $argument)
{
    switch($option)
    {
        case 'h':
            fprintf(STDERR, "Usage: php aws-cat-calculator.php [options]\r\n" .
                "  -h  Display this help.");
            break;
    }
}