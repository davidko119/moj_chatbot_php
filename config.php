<?php

// API kluc sa cita z prostredia (OPENAI_API_KEY), aby nebol natvrdo v kode.
define('OPENAI_API_KEY', trim((string) (getenv('OPENAI_API_KEY') ?: '')));

// Predvoleny model pre buduce napojenie API.
define('OPENAI_MODEL', 'gpt-4.1-mini');

