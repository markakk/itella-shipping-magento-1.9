<?php

$installer = $this;

$installer->startSetup();

$setup = $this;

$installer->addAttribute("order", "itella_pickup_point", array("type"=>"varchar"));
$installer->addAttribute("quote", "itella_pickup_point", array("type"=>"varchar"));

$installer->addAttribute("order", "manifest_generation_date", array("type"=>"varchar"));

$installer->endSetup();