<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'admin_notice_for_deleted_data']); // Add this line
        add_action('admin_init', [$this, 'handle_export_import']);
    }

    // Agregar la página de configuración en el menú de WordPress
    public function add_settings_page() {
        add_options_page(
            __('Configuración de Prompts Plugin', 'prompts-plugin'),
            __('Prompts Plugin', 'prompts-plugin'),
            'manage_options',
            'prompts-plugin',
            [$this, 'render_settings_page']
        );
    }

    // Renderizar la página de configuración
    public function render_settings_page() {
        ?>
        <div class="wrap">
        <h1><?php echo esc_html__('Configuración de Prompts Plugin', 'prompts-plugin'); ?></h1>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
        <?php
        settings_fields('prompts_plugin_settings');
        do_settings_sections('prompts-plugin');
        submit_button();
        ?>
        </form>

        <hr>
        <h2><?php echo esc_html__('Exportar e importar', 'prompts-plugin'); ?></h2>
        <div class="prompts-export-import-wrap" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px;">
            <div style="margin-bottom: 30px;">
                <h3><?php echo esc_html__('Exportar prompts', 'prompts-plugin'); ?></h3>
                <p><?php echo esc_html__('Descarga todos tus prompts (incluyendo categorías y descripciones) en un archivo JSON.', 'prompts-plugin'); ?></p>
                <form method="post">
                    <?php wp_nonce_field('prompts_export_action', 'prompts_export_nonce'); ?>
                    <input type="submit" name="prompts_export" class="button button-secondary" value="<?php echo esc_attr__('Descargar JSON de prompts', 'prompts-plugin'); ?>">
                </form>
            </div>

            <div>
                <h3><?php echo esc_html__('Importar prompts', 'prompts-plugin'); ?></h3>
                <p><?php echo esc_html__('Sube un archivo JSON para añadir nuevos prompts a tu colección.', 'prompts-plugin'); ?></p>
                <div id="prompts-import-container">
                    <input type="file" id="prompts-import-file" accept=".json">
                    <p>
                        <input type="checkbox" id="overwrite_existing" value="1">
                        <label for="overwrite_existing"><?php echo esc_html__('Sobrescribir prompts si el título ya existe', 'prompts-plugin'); ?></label>
                    </p>
                    <p class="submit" style="padding-left: 0;">
                        <button type="button" id="start-import" class="button button-primary"><?php echo esc_html__('Importar desde JSON', 'prompts-plugin'); ?></button>
                    </p>

                    <div id="import-progress-wrapper" style="display:none; margin-top: 20px;">
                        <progress id="import-progress-bar" value="0" max="100" style="width: 100%; height: 25px;"></progress>
                        <p id="import-status-text" style="font-weight: bold; margin-top: 5px;"></p>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <?php
    }

    // Registrar configuración
    public function register_settings() {
        register_setting('prompts_plugin_settings', 'prompts_plugin_delete_data_on_uninstall');

        add_settings_section(
            'prompts_plugin_main_section',
            __('Opciones de desinstalación', 'prompts-plugin'),
            null,
            'prompts-plugin'
        );

        add_settings_field(
            'prompts_plugin_delete_data_on_uninstall',
            __('Eliminar datos al desinstalar', 'prompts-plugin'),
            [$this, 'delete_data_on_uninstall_callback'],
            'prompts-plugin',
            'prompts_plugin_main_section'
        );
    }

    // Callback para mostrar el checkbox
    public function delete_data_on_uninstall_callback() {
        $option = get_option('prompts_plugin_delete_data_on_uninstall');
        ?>
        <input type="checkbox" id="prompts_plugin_delete_data_on_uninstall" name="prompts_plugin_delete_data_on_uninstall" value="1" <?php checked(1, $option, true); ?> />
        <label for="prompts_plugin_delete_data_on_uninstall"><?php echo esc_html__('Eliminar todos los datos al desinstalar.', 'prompts-plugin'); ?></label>
        <?php
    }

    public function admin_notice_for_deleted_data() {
        if (get_transient('prompts_plugin_data_deleted_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Se eliminaron todos los datos del plugin Prompts.', 'prompts-plugin'); ?></p>
            </div>
            <?php
            delete_transient('prompts_plugin_data_deleted_notice'); // Delete the transient after showing the notice
        }
    }

    /**
     * Maneja las acciones de exportación e importación al cargar el admin
     */
    public function handle_export_import() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'prompts-plugin') {
            return;
        }

        // Solo manejamos exportación sincrónica aquí
        if (isset($_POST['prompts_export']) && check_admin_referer('prompts_export_action', 'prompts_export_nonce')) {
            $this->process_export();
        }
    }

    /**
     * Procesa la exportación de todos los prompts a un archivo JSON.
     */
    private function process_export() {
        $query = new WP_Query([
            'post_type'      => 'prompt',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ]);
        $export_data = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();

                // Obtenemos los términos y extraemos solo los nombres de forma segura
                $terms = get_the_terms($id, 'categoria-de-prompt');
                $category_names = ($terms && !is_wp_error($terms)) ? wp_list_pluck($terms, 'name') : [];

                $export_data[] = [
                    'title'       => get_the_title(),
                    'description' => get_post_meta($id, 'prompt_description', true),
                    'content'     => get_post_meta($id, 'prompt_content', true),
                    'categories'  => array_values($category_names),
                ];
            }
            wp_reset_postdata();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=prompts-export-' . date('Y-m-d') . '.json');
        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
