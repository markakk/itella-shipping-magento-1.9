<?php

$installer = $this;

$installer->startSetup();

$setup = $this;

$installer->addAttribute("order", "itella_services", array("type"=>"varchar"));

$installer->endSetup();