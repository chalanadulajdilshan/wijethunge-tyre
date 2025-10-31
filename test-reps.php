<?php
include 'class/include.php';

echo "<h2>Testing Marketing Executive / Sales Rep Loading</h2>";

$marketingExec = new MarketingExecutive(null);

echo "<h3>1. Testing getAllExecutives()</h3>";
$executives = $marketingExec->getAllExecutives();
echo "Found " . count($executives) . " executives<br>";
echo "<pre>";
print_r($executives);
echo "</pre>";

echo "<h3>2. Testing getActiveExecutives()</h3>";
$activeExecs = $marketingExec->getActiveExecutives();
echo "Found " . count($activeExecs) . " active executives<br>";
echo "<pre>";
print_r($activeExecs);
echo "</pre>";

echo "<h3>3. Testing getAllSalesReps()</h3>";
$salesReps = $marketingExec->getAllSalesReps();
echo "Found " . count($salesReps) . " sales reps<br>";
echo "<pre>";
print_r($salesReps);
echo "</pre>";

echo "<h3>4. Testing all()</h3>";
$all = $marketingExec->all();
echo "Found " . count($all) . " total records<br>";
echo "<pre>";
print_r($all);
echo "</pre>";
?>
