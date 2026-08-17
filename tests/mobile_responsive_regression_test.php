<?php

declare(strict_types=1);

function assertCondition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("FAILED: " . $message);
    }
}

$root = dirname(__DIR__);
$mainLayout = file_get_contents($root . '/application/Views/layouts/main.php');
$header = file_get_contents($root . '/application/Views/layouts/header.php');
$staffSidebar = file_get_contents($root . '/application/Views/layouts/sidebar-staff.php');
$clientSidebar = file_get_contents($root . '/application/Views/layouts/sidebar-client.php');
$staffDashboard = file_get_contents($root . '/application/Views/staff/dashboard.php');
$staffClients = file_get_contents($root . '/application/Views/staff/clients/index.php');
$appJs = file_get_contents($root . '/public/assets/js/app.js');

echo "Running TriNova Staff Portal Mobile Responsive Regression Checks...\n";

// 1. Check no broken fixed bottom bar on mobile
assertCondition(!str_contains($mainLayout, 'bottom: 0') || !str_contains($mainLayout, 'height: 76px'), 'Old broken bottom navbar height rule found in main.php.');

// 2. Check layout wrapper
assertCondition(str_contains($mainLayout, 'class="tn-layout"'), 'Main layout must have .tn-layout container.');
assertCondition(str_contains($mainLayout, 'tn-mobile-overlay'), 'Main layout must have .tn-mobile-overlay backdrop.');
assertCondition(str_contains($mainLayout, 'id="tnMobileOverlay"'), 'Main layout must have id="tnMobileOverlay".');

// 3. Check tablet query is strictly scoped (769px to 1080px)
assertCondition(str_contains($mainLayout, '@media (min-width: 769px) and (max-width: 1080px)'), 'Intermediate tablet sidebar must be scoped between 769px and 1080px.');

// 4. Check mobile drawer rules (<= 768px)
assertCondition(str_contains($mainLayout, '@media (max-width: 768px)'), 'Mobile layout rules must include @media (max-width: 768px).');
assertCondition(str_contains($mainLayout, 'transform: translateX(-105%)'), 'Off-canvas drawer must default to off-screen transform.');
assertCondition(str_contains($mainLayout, 'body.tn-mobile-menu-open .tn-side') && str_contains($mainLayout, 'transform: translateX(0)'), 'Opening drawer must slide sidebar in with translateX(0).');
assertCondition((str_contains($mainLayout, 'body.tn-mobile-menu-open .tn-mobile-overlay') || str_contains($mainLayout, 'body.tn-mobile-menu-open .sidebar-overlay')) && str_contains($mainLayout, 'opacity: 1'), 'Opening drawer must activate mobile overlay.');
assertCondition(str_contains($mainLayout, 'body.tn-mobile-menu-open') && str_contains($mainLayout, 'overflow: hidden'), 'Opening drawer must lock body scrolling.');

// 5. Check Header hamburger toggle & ARIA
assertCondition(str_contains($header, 'id="tnMobileMenuToggle"'), 'Header must contain id="tnMobileMenuToggle".');
assertCondition(str_contains($header, 'aria-label="Open navigation"') || str_contains($header, 'aria-label="Toggle Navigation Menu"') || str_contains($header, 'aria-label="Open navigation menu"'), 'Hamburger button must have accessible aria-label.');
assertCondition(str_contains($header, 'aria-expanded="false"'), 'Hamburger button must have aria-expanded attribute.');
assertCondition(str_contains($header, 'aria-controls="tnStaffSidebar tnClientSidebar"'), 'Hamburger button must specify aria-controls.');

// 6. Check Sidebars close button & ARIA IDs
assertCondition(str_contains($staffSidebar, 'id="tnStaffSidebar"'), 'Staff sidebar must have id="tnStaffSidebar".');
assertCondition(str_contains($staffSidebar, 'id="tnMobileMenuCloseStaff"'), 'Staff sidebar must have close button id="tnMobileMenuCloseStaff".');
assertCondition(str_contains($clientSidebar, 'id="tnClientSidebar"'), 'Client sidebar must have id="tnClientSidebar".');
assertCondition(str_contains($clientSidebar, 'id="tnMobileMenuCloseClient"'), 'Client sidebar must have close button id="tnMobileMenuCloseClient".');

// 7. Check App JS mobile menu handling
assertCondition(str_contains($appJs, 'function toggleMobileMenu'), 'app.js must define toggleMobileMenu.');
assertCondition(str_contains($appJs, 'function initialiseMobileMenu'), 'app.js must define initialiseMobileMenu.');
assertCondition(str_contains($appJs, 'tn-mobile-menu-open'), 'app.js must toggle tn-mobile-menu-open on body.');
assertCondition(str_contains($appJs, 'Escape'), 'app.js must handle Escape key to close mobile menu.');

// 8. Check Clients Table mobile card transformation and word wrapping
assertCondition(str_contains($mainLayout, '#clientsTable .client-row'), 'CSS must define card styles for #clientsTable .client-row on mobile.');
assertCondition(str_contains($mainLayout, 'overflow-wrap: anywhere'), 'CSS must enforce overflow-wrap: anywhere on client name and email.');
assertCondition(str_contains($mainLayout, 'word-break: break-word'), 'CSS must enforce word-break: break-word.');

// 9. Check Safe Area Insets & Dynamic Viewport Height
assertCondition(str_contains($mainLayout, 'env(safe-area-inset-bottom)'), 'CSS must handle safe-area-inset-bottom.');
assertCondition(str_contains($mainLayout, '100dvh'), 'CSS must support 100dvh for mobile browsers.');

// 10. Check Z-Index Layering
assertCondition(str_contains($mainLayout, 'z-index: 20'), 'Topbar must have z-index: 20.');
assertCondition(str_contains($mainLayout, 'z-index: 40'), 'Overlay backdrop must have z-index: 40.');
assertCondition(str_contains($mainLayout, 'z-index: 50'), 'Off-canvas drawer must have z-index: 50.');

echo "All 10 TriNova Mobile Responsive Regression Checks Passed Successfully!\n";
