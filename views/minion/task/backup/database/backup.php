Backed up <?php echo count($instances); ?> databases

<?php if (count($instances) > 0): ?>
Instance	Status
##################
<?php
foreach ($instances as $instance => $status)
{
	echo "* $instance\t$status\n";
}
endif;
?>