#!/usr/bin/env php
<?php

// À lancer après toute modification d'un bundle.meta.json : « pnpm registry ».
require_once __DIR__ . '/utils/regenerate-tools-registry.php';

regenerate_tools_registry(dirname(__DIR__));
