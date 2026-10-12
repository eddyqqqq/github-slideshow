<?php
/**
 * Pavva Categories – câmpurile categoriilor de produse (admin)
 *
 * Aceleași câmpuri și aceeași salvare ca în „Pavva Categories” 1.0.2.
 *
 * @package Pavva_Categories
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_C_Fields {

    const META_HERO_ID          = Pavva_C_Data::META_HERO_ID;
    const META_INTRO            = Pavva_C_Data::META_INTRO;
    const META_BENEFITS_TITLE   = Pavva_C_Data::META_BENEFITS_TITLE;
    const META_BENEFITS_CONTENT = Pavva_C_Data::META_BENEFITS_CONTENT;
    const META_WHY_TITLE        = Pavva_C_Data::META_WHY_TITLE;
    const META_WHY_CONTENT      = Pavva_C_Data::META_WHY_CONTENT;

    public function __construct() {
        add_action( 'product_cat_add_form_fields', array( $this, 'add_fields' ) );
        add_action( 'product_cat_edit_form_fields', array( $this, 'edit_fields' ), 10, 2 );
        add_action( 'created_product_cat', array( $this, 'save_fields' ) );
        add_action( 'edited_product_cat', array( $this, 'save_fields' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
    }

    public function enqueue_admin( $hook ) {
        if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) return;

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'product_cat' !== $screen->taxonomy ) return;

        wp_enqueue_media();
        wp_enqueue_script(
            'pavva-categories-admin',
            PAVVA_Categories::url( 'assets/admin.js' ),
            array( 'jquery' ),
            PAVVA_Categories::VERSION,
            true
        );
    }

    private function image_field_html( $image_id = 0 ) {
        $image_id = absint( $image_id );
        $url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

        ob_start();
        ?>
        <div class="pavva-category-image-field">
            <input type="hidden" name="pavva_cat_hero_image_id" class="pavva-category-image-id" value="<?php echo esc_attr( $image_id ); ?>">
            <div class="pavva-category-image-preview" style="margin:8px 0">
                <?php if ( $url ) : ?>
                    <img src="<?php echo esc_url( $url ); ?>" alt="" style="max-width:260px;height:auto;display:block">
                <?php endif; ?>
            </div>
            <button type="button" class="button pavva-category-select-image">Selectează imaginea</button>
            <button type="button" class="button-link-delete pavva-category-remove-image"<?php echo $url ? '' : ' style="display:none"'; ?>>Elimină</button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function editor_settings( $textarea_name, $rows = 8 ) {
        return array(
            'textarea_name' => $textarea_name,
            'textarea_rows' => $rows,
            'media_buttons' => false,
            'teeny'         => true,
            'quicktags'     => true,
        );
    }

    public function add_fields() {
        wp_nonce_field( 'pavva_categories_save', 'pavva_categories_nonce' );
        ?>
        <div class="form-field">
            <label>Imagine categorie</label>
            <?php echo $this->image_field_html(); ?>
        </div>
        <div class="form-field">
            <label>Text intro categorie</label>
            <textarea name="pavva_cat_intro" rows="5"></textarea>
        </div>
        <div class="form-field">
            <label>Titlu secțiune Avantaje</label>
            <input type="text" name="pavva_cat_benefits_title" value="">
        </div>
        <div class="form-field">
            <label>Conținut Avantaje</label>
            <textarea name="pavva_cat_benefits_content" rows="10"></textarea>
        </div>
        <div class="form-field">
            <label>Titlu secțiune De ce să alegi</label>
            <input type="text" name="pavva_cat_why_title" value="">
        </div>
        <div class="form-field">
            <label>Conținut De ce să alegi</label>
            <textarea name="pavva_cat_why_content" rows="10"></textarea>
        </div>
        <?php
    }

    public function edit_fields( $term, $taxonomy ) {
        $hero_id = absint( get_term_meta( $term->term_id, self::META_HERO_ID, true ) );
        $intro   = get_term_meta( $term->term_id, self::META_INTRO, true );
        $bt      = get_term_meta( $term->term_id, self::META_BENEFITS_TITLE, true );
        $bc      = get_term_meta( $term->term_id, self::META_BENEFITS_CONTENT, true );
        $wt      = get_term_meta( $term->term_id, self::META_WHY_TITLE, true );
        $wc      = get_term_meta( $term->term_id, self::META_WHY_CONTENT, true );

        wp_nonce_field( 'pavva_categories_save', 'pavva_categories_nonce' );
        ?>
        <tr class="form-field">
            <th scope="row"><label>Imagine categorie</label></th>
            <td><?php echo $this->image_field_html( $hero_id ); ?></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>Text intro categorie</label></th>
            <td><?php wp_editor( $intro, 'pavva_cat_intro_editor', $this->editor_settings( 'pavva_cat_intro', 6 ) ); ?></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>Titlu secțiune Avantaje</label></th>
            <td><input type="text" name="pavva_cat_benefits_title" value="<?php echo esc_attr( $bt ); ?>"></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>Conținut Avantaje</label></th>
            <td><?php wp_editor( $bc, 'pavva_cat_benefits_editor', $this->editor_settings( 'pavva_cat_benefits_content', 12 ) ); ?></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>Titlu secțiune De ce să alegi</label></th>
            <td><input type="text" name="pavva_cat_why_title" value="<?php echo esc_attr( $wt ); ?>"></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>Conținut De ce să alegi</label></th>
            <td><?php wp_editor( $wc, 'pavva_cat_why_editor', $this->editor_settings( 'pavva_cat_why_content', 12 ) ); ?></td>
        </tr>
        <?php
    }

    public function save_fields( $term_id ) {
        if ( ! isset( $_POST['pavva_categories_nonce'] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pavva_categories_nonce'] ) ), 'pavva_categories_save' ) ) return;
        if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_woocommerce' ) ) return;

        $values = array(
            self::META_HERO_ID => isset( $_POST['pavva_cat_hero_image_id'] ) ? absint( $_POST['pavva_cat_hero_image_id'] ) : 0,
            self::META_INTRO => isset( $_POST['pavva_cat_intro'] ) ? wp_kses_post( wp_unslash( $_POST['pavva_cat_intro'] ) ) : '',
            self::META_BENEFITS_TITLE => isset( $_POST['pavva_cat_benefits_title'] ) ? sanitize_text_field( wp_unslash( $_POST['pavva_cat_benefits_title'] ) ) : '',
            self::META_BENEFITS_CONTENT => isset( $_POST['pavva_cat_benefits_content'] ) ? wp_kses_post( wp_unslash( $_POST['pavva_cat_benefits_content'] ) ) : '',
            self::META_WHY_TITLE => isset( $_POST['pavva_cat_why_title'] ) ? sanitize_text_field( wp_unslash( $_POST['pavva_cat_why_title'] ) ) : '',
            self::META_WHY_CONTENT => isset( $_POST['pavva_cat_why_content'] ) ? wp_kses_post( wp_unslash( $_POST['pavva_cat_why_content'] ) ) : '',
        );

        foreach ( $values as $key => $value ) {
            if ( '' === $value || 0 === $value ) delete_term_meta( $term_id, $key );
            else update_term_meta( $term_id, $key, $value );
        }
    }
}
