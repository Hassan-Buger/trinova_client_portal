<?php
$uri = $_SERVER['REQUEST_URI'] ?? '';

$nav = [
    ['href' => '/client/dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['href' => '/client/documents/upload', 'label' => 'Upload Documents', 'icon' => 'upload'],
    ['href' => '/client/documents/my-uploads', 'label' => 'My Uploads', 'icon' => 'folder'],
    ['href' => '/client/documents/trinova', 'label' => 'Documents from TriNova', 'icon' => 'file'],
    ['href' => '/client/messages', 'label' => 'Messages', 'icon' => 'chat', 'badge' => '1'],
    ['href' => '/client/meetings/book', 'label' => 'Book a Meeting', 'icon' => 'calCheck'],
    ['href' => '/client/deadlines', 'label' => 'Important Dates', 'icon' => 'cal'],
    ['href' => '/client/aml', 'label' => 'AML', 'icon' => 'shield'],
    ['href' => '/client/profile/details', 'label' => 'My Details', 'icon' => 'user'],
];

function renderNavIcon(string $ic): string {
    $map = [
        'grid' => '<path d="M3 3h7v7H3z M14 3h7v7h-7z M14 14h7v7h-7z M3 14h7v7H3z"/>',
        'upload' => '<path d="M12 15V3 m7 8 5-5 5 5 M20 16.5V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5"/>',
        'folder' => '<path d="M3 8a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'file' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z M14 3v6h6 M9 14h6 M9 18h4"/>',
        'chat' => '<path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/>',
        'calCheck' => '<path d="M8 2v4 M16 2v4 M4 9h16 M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z m9 15 2 2 4-4"/>',
        'cal' => '<path d="M8 2v4 M16 2v4 M4 9h16 M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z m9 11.5 2 2 4-4"/>',
        'user' => '<path d="M19 21a7 7 0 0 0-14 0 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>',
    ];
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($map[$ic] ?? '') . '</svg>';
}
?>

<aside class="tn-side" style="width:262px;flex-shrink:0;background:#fff;border-right:1px solid rgba(20,60,50,.07);display:flex;flex-direction:column;padding:22px 16px;position:sticky;top:0;height:100vh">
    <div style="display:flex;align-items:center;gap:11px;padding:4px 8px 22px">
        <svg width="38" height="38" viewBox="0 0 40 40" fill="none">
            <rect x="0" y="0" width="40" height="40" rx="12" fill="#0d9488"/>
            <path d="M20 9 L30 28 L10 28 Z" fill="#fff" opacity="0.92"/>
            <path d="M20 17 L26 28 L14 28 Z" fill="#ef8f3c"/>
        </svg>
        <div class="tn-brandword">
            <div style="font-weight:800;font-size:15.5px;letter-spacing:-.01em;line-height:1.1">TriNova</div>
            <div style="font-size:9.5px;color:#8a9a94;font-weight:700;letter-spacing:.16em;text-transform:uppercase">Client Hub</div>
        </div>
    </div>
    <nav style="display:flex;flex-direction:column;gap:3px;flex:1">
        <?php foreach ($nav as $item): ?>
            <?php
            $active = (rtrim($uri, '/') === rtrim($item['href'], '/'));
            $style = 'display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:15px;cursor:pointer;font-weight:600;font-size:14.5px;transition:background .12s;' .
                     ($active ? 'background:#dff1ee;color:#0f766e;' : 'color:#5f726c;');
            ?>
            <a href="<?= $item['href'] ?>" class="tn-navitem" style="<?= $style ?>" title="<?= $item['label'] ?>">
                <?= renderNavIcon($item['icon']) ?>
                <span class="tn-navlabel"><?= $item['label'] ?></span>
                <?php if (!empty($item['badge'])): ?>
                    <span class="tn-navlabel" style="margin-left:auto;background:#0d9488;color:#fff;font-size:11px;font-weight:700;min-width:20px;height:20px;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:0 6px">
                        <?= $item['badge'] ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <a href="/logout" class="tn-navitem" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:15px;cursor:pointer;color:#61756e;font-weight:600;font-size:14.5px;margin-top:8px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 m16 17 5-5-5-5 M21 12H9"/>
        </svg>
        <span class="tn-logout-label tn-navlabel">Logout</span>
    </a>
</aside>
