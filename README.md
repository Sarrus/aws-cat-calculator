# AWS Cost Allocation Tag Calculator

A simple application for calculating bill share by AWS Cost Allocation Tag

## Purpose

I maintain a number of services for my customers within AWS. It is useful for me to be able to calculate 
how much of my AWS bill is attributed to each customer. I do this by tagging each customer's resources 
within AWS using the customer's name and then downloading a monthly Cost Allocation Report which AWS Billing
generates. 

The report goes into a huge amount of detail but all I really need from it is the total share of my monthly
bill each customer is responsible for. 

This script is written in PHP and takes a path to the report as an argument. It sums up each customer's share
and is also able to calculate what that share equates to in a local currency.

As always with my public repos. PRs of fixes and features are welcomed gratefully. 

## Setup

Before running this script, you will need to have configured AWS to generate Cost Allocation Reports
and tagged your resources to indicate which customer they belong to. 

To tag your AWS resources so that their usage can be totalled by this script add a tag to each resource 
in turn called 'Customer' and give it a name that uniquely identifies the customer that that the resource 
is used to service. This process can be streamlined by using the AWS Tag Editor.

To enable Cost Allocation Reports follow the steps under 'Setting up a monthly cost allocation report' 
here: https://docs.aws.amazon.com/awsaccountbilling/latest/aboutv2/configurecostallocreport.html

When enabling Cost Allocation Reports, you will need to configure the report to include the 'Customer'
tag. AWS will not allow the tag to be enabled until it has been assigned to resources. It can take up to 
24 hours for a tag to be available to include in a report after it has been assigned to a resource.

## Usage

The full options for the script can be displayed using the -h flag:

    Usage: php aws-cat-calculator.php [options]
    -c  Convert to local currency (must specify the bill total in local currency)  
    -f  Read CAT report from this file (required)
    -h  Display this help
    -i  Filter by this invoice number
    -s  Local currency symbol

Once a new Cost Allocation Report has been generated, download it from S3 and run the script:

    php aws-cat-calculator.php -f [path to CAR]

It will output the total usage cost for each customer. Any resource that was not tagged to a customer is 
counted as internal usage:

    Warning: Invoice number (option -i) not specified. Output may include non-usage charges such as EC2 reservations.
    Total internal usage: $221.88, Percentage of total: 56%
    Total usage for Aarons-Ardvarks: $0.00, Percentage of total: 0%
    Total usage for Bills-Bikes: $5.52, Percentage of total: 1%
    Total usage for Charlies-Choppers: $121.50, Percentage of total: 31%
    Total usage for Daves-Diamonds: $45.07, Percentage of total: 11%
    Total usage for Emmas-Explosives: $1.36, Percentage of total: 0%
    Grand total: $395.33

The warning at the top indicates that an invoice number was not specified. AWS Cost Allocation Reports contain
non-usage charges like EC2 / RDS reservations and domain renewals. Since these resources are usually accounted
for separately, it is best to filter the results by invoice number to exclude them. Use the -i flag and the 
number of the usage invoice you wish to total:

    php aws-cat-calculator.php -f 0123456789-aws-cost-allocation-2021-10.csv -i 1NV01C3-NUMB3R

If you pay your AWS bill in a currency other than USD, the CAR will still be in dollars. To calculate each customer's 
share in your local currency, use the -c flag with the invoice total in your local currency. You can also use
the -s flag to specify a currency symbol to use in the output:

    php aws-cat-calculator.php -f 0123456789-aws-cost-allocation-2021-10.csv -i 1NV01C3-NUMB3R -c 123.45 -s §

The conversion to local currency will be appended to the output:

    Total internal usage: $175.08, Percentage of total: 50% In local currency: §62.01
    Total usage for Aarons-Ardvarks: $0.00, Percentage of total: 0% In local currency: §0.00
    Total usage for Bills-Bikes: $5.52, Percentage of total: 2% In local currency: §1.95
    Total usage for Charlies-Choppers: $121.50, Percentage of total: 35% In local currency: §43.04
    Total usage for Daves-Diamonds: $45.07, Percentage of total: 13% In local currency: §15.96
    Total usage for Emmas-Explosives: $1.36, Percentage of total: 0% In local currency: §0.48
    Grand total: $348.53
