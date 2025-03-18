<?php
class Prompts_Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'admin_notice_for_deleted_data']); // Add this line
    }

    // Agregar la página de configuración en el menú de WordPress
    public function add_settings_page() {
        add_options_page(
            'Configuración de Prompts Plugin',
            'Prompts Plugin',
            'manage_options',
            'prompts-plugin',
            [$this, 'render_settings_page']
        );
    }

    // Renderizar la página de configuración
    public function render_settings_page() {
        ?>
        <div class="wrap">
        <h1>Configuración de Prompts Plugin</h1>
        <form method="post" action="options.php">
        <?php
        settings_fields('prompts_plugin_settings');
        do_settings_sections('prompts-plugin');
        submit_button();
        ?>
        </form>
        </div>
        <?php
    }

    // Registrar configuración
    public function register_settings() {
        register_setting('prompts_plugin_settings', 'prompts_plugin_delete_data_on_uninstall');

        add_settings_section('prompts_plugin_main_section', 'Opciones de Desinstalación', null, 'prompts-plugin');

        add_settings_field(
            'prompts_plugin_delete_data_on_uninstall',
            'Eliminar datos al desinstalar',
            [$this, 'delete_data_on_uninstall_callback'],
            'prompts-plugin',
            'prompts_plugin_main_section'
        );
    }

    // Callback para mostrar el checkbox
    public function delete_data_on_uninstall_callback() {
        $option = get_option('prompts_plugin_delete_data_on_uninstall');
        ?>
        <input type="checkbox" name="prompts_plugin_delete_data_on_uninstall" value="1" <?php checked(1, $option, true); ?> />
        <label for="prompts_plugin_delete_data_on_uninstall">Eliminar todos los datos al desinstalar.</label>
        <?php
    }

    public function admin_notice_for_deleted_data() {
        if (get_transient('prompts_plugin_data_deleted_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
            <p><?php _e('All data related to the Prompts Plugin has been deleted.', 'prompts-plugin'); ?></p>
            </div>
            <?php
            delete_transient('prompts_plugin_data_deleted_notice'); // Delete the transient after showing the notice
        }
    }
}
