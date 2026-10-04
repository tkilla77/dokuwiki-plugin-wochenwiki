<?php

/**
 * Options for the wochenwiki plugin
 */

$meta['label'] = ['string'];
$meta['level'] = ['multichoice', '_choices' => [1, 2, 3, 4, 5]];
$meta['boundary'] = ['numeric', '_min' => 1, '_max' => 52];
