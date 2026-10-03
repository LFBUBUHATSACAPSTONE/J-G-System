<?php

// Entry point for the web routes. Each area has one aggregator file that requires one file
// per section, so the chain is:
//
//   web.php  ->  user.php   ->  user/<section>.php
//   web.php  ->  admin.php  ->  admin/<section>.php
//
// Add new routes to the section file they belong to, not here.
require __DIR__ . '/user.php';
require __DIR__ . '/admin.php';
