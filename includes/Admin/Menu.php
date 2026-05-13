<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class Menu
{
    private const ROOT_SLUG = 'performance-toolkit';

    /**
     * @var array<string, AdminPageInterface>
     */
    private array $pages;

    /**
     * @var string[]
     */
    private array $page_hooks = array();

    /**
     * @param AdminPageInterface[] $pages
     */
    public function __construct(array $pages)
    {
        $this->pages = array();

        foreach ($pages as $page) {
            $this->pages[$page->slug()] = $page;
        }
    }

    public function register(): void
    {
        add_action('admin_menu', array($this, 'addMenuPage'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));
        add_action('admin_head', array($this, 'printMenuIconStyles'));
    }

    public function addMenuPage(): void
    {
        $root_page = $this->pages[self::ROOT_SLUG] ?? null;

        if (! $root_page instanceof AdminPageInterface) {
            return;
        }

        $top_level_hook = add_menu_page(
            $root_page->pageTitle(),
            __('Performance', 'performance-toolkit'),
            'manage_options',
            self::ROOT_SLUG,
            array($this, 'renderCurrentPage'),
            PERFORMANCE_TOOLKIT_URL . 'assets/img/performance-toolkit-currentcolor.svg',
            81
        );

        if (is_string($top_level_hook)) {
            $this->page_hooks[] = $top_level_hook;
        }

        foreach ($this->pages as $slug => $page) {
            $submenu_hook = add_submenu_page(
                self::ROOT_SLUG,
                $page->pageTitle(),
                $page->menuTitle(),
                'manage_options',
                $slug,
                array($this, 'renderCurrentPage')
            );

            if (is_string($submenu_hook)) {
                $this->page_hooks[] = $submenu_hook;
            }
        }
    }

    public function renderCurrentPage(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $page_slug = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : self::ROOT_SLUG;
        $current_page = $this->pages[$page_slug] ?? ($this->pages[self::ROOT_SLUG] ?? null);

        if (! $current_page instanceof AdminPageInterface) {
            return;
        }

        AdminShell::render($current_page, $this->pages);
    }

    public function enqueueAssets(string $hook_suffix): void
    {
        if (! in_array($hook_suffix, $this->page_hooks, true)) {
            return;
        }

        $style_path = PERFORMANCE_TOOLKIT_PATH . 'dist/admin.css';
        $style_url = PERFORMANCE_TOOLKIT_URL . 'dist/admin.css';

        wp_enqueue_style(
            'performance-toolkit-admin',
            $style_url,
            array(),
            file_exists($style_path) ? (string) filemtime($style_path) : PERFORMANCE_TOOLKIT_VERSION
        );
    }

    public function printMenuIconStyles(): void
    {
        $icon_url = esc_url(PERFORMANCE_TOOLKIT_URL . 'assets/img/performance-toolkit-currentcolor.svg');

        echo '<style id="performance-toolkit-menu-icon">#adminmenu .toplevel_page_performance-toolkit .wp-menu-image img{display:none}#adminmenu .toplevel_page_performance-toolkit .wp-menu-image{color:inherit}#adminmenu .toplevel_page_performance-toolkit .wp-menu-image:before{content:"";display:block;width:28px;height:28px;margin:1px auto 0;transform:translateY(-4px);background-color:currentColor;-webkit-mask-image:url("' . $icon_url . '");mask-image:url("' . $icon_url . '");-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;-webkit-mask-size:28px 28px;mask-size:28px 28px}</style>';
    }
}




