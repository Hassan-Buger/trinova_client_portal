<?php

declare(strict_types=1);

function expectProfile(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$view = file_get_contents($root . '/application/Views/staff/clients/show.php');
$controller = file_get_contents($root . '/application/Controllers/Staff/ClientController.php');

expectProfile(str_contains($view, 'class="tn-screen client-profile"'), 'The premium client profile shell is missing.');
expectProfile(str_contains($view, 'class="cp-layout"') && str_contains($view, '@media(max-width:520px)'), 'The client profile responsive layout is missing.');
expectProfile(str_contains($view, "(string) \$key === 'csv_source_data'"), 'Raw CSV source data is not filtered from the client profile.');
expectProfile(str_contains($view, 'aria-labelledby="entities-title"') && str_contains($view, 'aria-labelledby="activity-title"'), 'The client profile sections are not labelled accessibly.');
expectProfile(str_contains($view, 'min="<?= date(\'Y-m-d\') ?>"'), 'New client deadlines do not prevent past dates.');
expectProfile(str_contains($view, 'data-ajax-form class="cp-access-row"'), 'Portal-access removal is not AJAX enhanced.');
expectProfile(!str_contains($view, 'ðŸ') && !str_contains($view, 'Â'), 'The client profile still contains mojibake.');
expectProfile(str_contains($controller, "'pageTitle'   => 'Client profile'"), 'The global header still duplicates the client name.');

echo "Client profile view checks passed\n";
