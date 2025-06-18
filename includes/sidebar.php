<?php
// Active page logic for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$parent_page = ''; // For submenu handling

// Define menu structure (can be moved to a config or helper later)
$menu_items = [
    'dashboard.php' => ['icon' => 'bi-speedometer2', 'title' => 'Dashboard'],
    'business_info.php' => ['icon' => 'bi-info-circle-fill', 'title' => 'Business Info'],
    'About' => [ // Parent Menu Item
        'icon' => 'bi-file-person-fill',
        'title' => 'About Us',
        'submenu' => [
             'about_content.php' => ['icon' => 'bi-text-paragraph', 'title' => 'About Content'],
             'our_journey.php' => ['icon' => 'bi-signpost-split-fill', 'title' => 'Our Journey'],
             'our_values.php' => ['icon' => 'bi-gem', 'title' => 'Our Values'],
        ]
    ],
    'testimonials.php' => ['icon' => 'bi-chat-square-quote-fill', 'title' => 'Testimonials'],
    'industry_categories.php' => ['icon' => 'bi-tags-fill', 'title' => 'Industry Categories'],
    'projects.php' => ['icon' => 'bi-kanban-fill', 'title' => 'Projects'],
    'services.php' => ['icon' => 'bi-gear-wide-connected', 'title' => 'Services'],
    'hero_sliders.php' => ['icon' => 'bi-images', 'title' => 'Hero Sliders'],
    'contact_settings.php' => ['icon' => 'bi-telephone-inbound-fill', 'title' => 'Contact Settings'],
    'form_enquiries.php' => ['icon' => 'bi-envelope-paper-fill', 'title' => 'Enquiries'],
    'file_manager_page.php' => ['icon' => 'bi-folder-fill', 'title' => 'File Manager'], // Example link to a dedicated FM page
];

// Determine parent page for active submenu item
foreach ($menu_items as $url => $item) {
    if (isset($item['submenu'])) {
        foreach ($item['submenu'] as $sub_url => $sub_item) {
            if ($current_page == $sub_url) {
                $parent_page = $url;
                break 2;
            }
        }
    }
}
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="sidebar-header">
            Navigation
        </div>
        <ul class="nav flex-column">
            <?php foreach ($menu_items as $url => $item): ?>
                <?php if (isset($item['submenu'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($parent_page == $url) ? 'active' : ''; ?>" href="#<?php echo str_replace(' ', '', $url); ?>Submenu" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo ($parent_page == $url) ? 'true' : 'false'; ?>" aria-controls="<?php echo str_replace(' ', '', $url); ?>Submenu">
                            <i class="bi <?php echo $item['icon']; ?>"></i>
                            <?php echo htmlspecialchars($item['title']); ?> <i class="bi bi-chevron-down float-end"></i>
                        </a>
                        <div class="collapse <?php echo ($parent_page == $url) ? 'show' : ''; ?>" id="<?php echo str_replace(' ', '', $url); ?>Submenu">
                            <ul class="nav flex-column ms-3">
                                <?php foreach ($item['submenu'] as $sub_url => $sub_item): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo ($current_page == $sub_url) ? 'active' : ''; ?>" href="<?php echo SITE_URL . '/' . $sub_url; ?>">
                                            <i class="bi <?php echo $sub_item['icon']; ?>"></i> <?php echo htmlspecialchars($sub_item['title']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == $url) ? 'active' : ''; ?>" href="<?php echo SITE_URL . '/' . $url; ?>">
                            <i class="bi <?php echo $item['icon']; ?>"></i>
                            <?php echo htmlspecialchars($item['title']); ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
