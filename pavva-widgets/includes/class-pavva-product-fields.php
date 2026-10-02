<?php
/**
 * Pavva Widgets – câmpurile produselor (admin)
 *
 * Mutat din pluginul „Pavva Products” 4.10.30. Metodele de afișare și salvare
 * sunt copiate identic, deci câmpurile arată și se salvează exact ca înainte.
 * Se activează doar când pluginul „Pavva Products” este dezactivat.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Product_Fields {

    const META_CULORI = Pavva_W_Data::META_CULORI;
    const META_CARACT = Pavva_W_Data::META_CARACT;
    const META_DIM    = Pavva_W_Data::META_DIM;
    const META_AMEN   = Pavva_W_Data::META_AMEN;
    const META_FISE   = Pavva_W_Data::META_FISE;
    const META_DECL   = Pavva_W_Data::META_DECL;
    const META_DIM_LABELS = Pavva_W_Data::META_DIM_LABELS;
    const META_CULORI_COLS = Pavva_W_Data::META_CULORI_COLS;
    const META_DESCRIERE_SINTEZA = Pavva_W_Data::META_DESCRIERE_SINTEZA;

    const NONCE_ACTION = 'pavva_produse_salvare';
    const NONCE_NAME   = 'pavva_produse_nonce';

    public function __construct() {
        add_action( 'add_meta_boxes_product', array( $this, 'add_metaboxes' ) );
        add_action( 'save_post_product', array( $this, 'save_all' ), 10, 2 );

        // Reorganizare editor produs WooCommerce (ca în Pavva Products).
        add_action( 'load-post.php', array( $this, 'configure_product_editor_screen' ) );
        add_action( 'load-post-new.php', array( $this, 'configure_product_editor_screen' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
    }

    public function add_metaboxes() {
        // Folosim câmpurile native WooCommerce, dar le prezentăm într-o ordine clară pentru Pavva.
        remove_meta_box( 'postexcerpt', 'product', 'normal' );

        add_meta_box( 'pavva-short-description', 'Descriere scurtă', array( $this, 'box_short_description' ), 'product', 'normal', 'high' );
        add_meta_box( 'pavva-descriere-sinteza', 'Descriere sinteza', array( $this, 'box_descriere_sinteza' ), 'product', 'normal', 'high' );
        add_meta_box( 'pavva-caracteristici', 'Caracteristici', array( $this, 'box_caracteristici' ), 'product', 'normal', 'default' );
        add_meta_box( 'pavva-culori', 'Culori și finisaje', array( $this, 'box_culori' ), 'product', 'normal', 'default' );
        add_meta_box( 'pavva-dimensiuni', 'Dimensiuni și specificații', array( $this, 'box_dimensiuni' ), 'product', 'normal', 'default' );
        add_meta_box( 'pavva-amenajare', 'Variante de amenajare', array( $this, 'box_amenajare' ), 'product', 'normal', 'default' );
        add_meta_box( 'pavva-fise', 'Fișe tehnice', array( $this, 'box_fise' ), 'product', 'normal', 'default' );
        add_meta_box( 'pavva-declaratii', 'Declarații de performanță', array( $this, 'box_declaratii' ), 'product', 'normal', 'default' );
        add_meta_box( 'pavva-product-description', 'Descriere produs', array( $this, 'box_product_description' ), 'product', 'normal', 'low' );
    }

    public function configure_product_editor_screen() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'product' !== $screen->post_type ) return;

        // Ascundem editorul principal WordPress pentru produse.
        // Conținutul rămâne în post_content și este editat în metabox-ul „Descriere produs”.
        remove_post_type_support( 'product', 'editor' );
    }

    public function box_short_description( $post ) {
        wp_editor(
            $post->post_excerpt,
            'pavva_short_description_editor',
            array(
                'textarea_name' => 'excerpt',
                'textarea_rows' => 6,
                'media_buttons' => false,
                'teeny'         => true,
                'quicktags'     => true,
            )
        );
    }

    public function box_descriere_sinteza( $post ) {
        $value = get_post_meta( $post->ID, self::META_DESCRIERE_SINTEZA, true );

        echo '<p class="description">Text scurt folosit în cardurile de produs din paginile de categorie.</p>';
        echo '<textarea name="pavva_descriere_sinteza" rows="4" class="widefat">' . esc_textarea( $value ) . '</textarea>';
    }

    public function box_product_description( $post ) {
        wp_editor(
            $post->post_content,
            'pavva_product_description_editor',
            array(
                'textarea_name' => 'content',
                'textarea_rows' => 14,
                'media_buttons' => true,
                'teeny'         => false,
                'quicktags'     => true,
            )
        );
    }

    public function enqueue_admin( $hook ) {
        global $post_type;
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== $post_type ) return;

        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style( 'pavva-w-products-admin', PAVVA_WIDGETS_URL . 'assets/admin/products-admin.css', array(), PAVVA_WIDGETS_VERSION );
        wp_enqueue_script( 'pavva-w-products-admin', PAVVA_WIDGETS_URL . 'assets/admin/products-admin.js', array( 'jquery', 'jquery-ui-sortable' ), PAVVA_WIDGETS_VERSION, true );
        wp_localize_script( 'pavva-w-products-admin', 'PavvaAdmin', array(
            'mediaImageTitle' => 'Selectează imaginea',
            'mediaFileTitle'  => 'Selectează PDF-ul',
            'mediaButton'     => 'Folosește fișierul',
            'confirmDelete'   => 'Ștergi acest element?',
        ) );
    }

    private function items( $post_id, $key ) {
        $v = get_post_meta( $post_id, $key, true );
        return is_array( $v ) ? $v : array();
    }

    private function nonce() {
        static $done = false;
        if ( ! $done ) {
            wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
            $done = true;
        }
    }

    private function box_open( $text ) {
        $this->nonce();
        echo '<div class="pavva-admin-box"><p class="description">' . esc_html( $text ) . '</p>';
    }

    private function box_close() { echo '</div>'; }

    public function box_caracteristici( $post ) {
        $this->box_open( 'Adaugă iconurile și denumirile care trebuie afișate pentru produsul curent.' );
        $this->render_repeater( 'caracteristici', $this->items( $post->ID, self::META_CARACT ), array(
            'denumire' => array( 'label'=>'Denumire', 'type'=>'text', 'placeholder'=>'Ex.: Trafic auto' ),
            'icon_id'  => array( 'label'=>'Icon', 'type'=>'image' ),
        ), 'Adaugă caracteristică' );
        $this->box_close();
    }

    public function box_culori( $post ) {
        $this->box_open( 'Fiecare variantă aparține exclusiv acestui produs. Aceeași denumire poate avea alte imagini la alt produs.' );

        $cols = absint( get_post_meta( $post->ID, self::META_CULORI_COLS, true ) );
        if ( ! in_array( $cols, array( 3, 4 ), true ) ) $cols = 3;

        echo '<div class="pavva-admin-setting">';
        echo '<label for="pavva_culori_coloane"><strong>Coloane thumbnails</strong></label>';
        echo '<select id="pavva_culori_coloane" name="pavva_culori_coloane">';
        echo '<option value="3"' . selected( $cols, 3, false ) . '>3 coloane</option>';
        echo '<option value="4"' . selected( $cols, 4, false ) . '>4 coloane</option>';
        echo '</select>';
        echo '</div>';
        $this->render_repeater( 'culori', $this->items( $post->ID, self::META_CULORI ), array(
            'denumire'        => array( 'label'=>'Denumire variantă', 'type'=>'text', 'placeholder'=>'Ex.: Portocaliu-antracit' ),
            'thumbnail_id'    => array( 'label'=>'Thumbnail', 'type'=>'image' ),
            'imagine_mare_id' => array( 'label'=>'Imagine mare', 'type'=>'image' ),
        ), 'Adaugă variantă' );
        $this->box_close();
    }

    public function box_dimensiuni( $post ) {
        $this->box_open(
            'Adaugă câte un element pentru fiecare dimensiune / configurație. '
            . 'Fiecare element poate avea oricâte rânduri Etichetă + Valoare.'
        );

        $items = $this->items( $post->ID, self::META_DIM );

        echo '<div class="pavva-repeater pavva-dimensions-repeater" data-name="dimensiuni">';
        echo '<div class="pavva-repeater-list pavva-dimension-list">';

        foreach ( $items as $i => $item ) {
            $this->render_dimension_row( $i, $item, $post->ID );
        }

        echo '</div>';
        echo '<p><button type="button" class="button button-secondary pavva-add-dimension-row">+ Adaugă dimensiune</button></p>';

        echo '<script type="text/html" class="pavva-dimension-row-template">';
        $this->render_dimension_row( '{{DIM_INDEX}}', array(), $post->ID );
        echo '</script>';

        echo '<script type="text/html" class="pavva-dimension-spec-template">';
        $this->render_dimension_spec_row( '{{DIM_INDEX}}', '{{SPEC_INDEX}}', array() );
        echo '</script>';

        echo '</div>';

        echo '<p class="description">';
        echo 'Pentru produsele vechi, câmpurile fixe sunt convertite automat în rânduri dinamice la prima salvare. ';
        echo 'Ordinea rândurilor de aici este și ordinea din frontend.';
        echo '</p>';

        $this->box_close();
    }

    private function render_dimension_row( $index, $item, $product_id ) {
        $image_id = ! empty( $item['imagine_id'] ) ? absint( $item['imagine_id'] ) : 0;
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
        $specs = Pavva_W_Data::dimension_item_specs( $item, $product_id );

        echo '<div class="pavva-row pavva-dimension-row" data-dim-index="' . esc_attr( $index ) . '">';
        echo '<div class="pavva-row-head">';
        echo '<span class="pavva-handle">☰</span><strong>Dimensiune / configurație</strong>';

        $visibility_input = 'pavva_dimensiuni[' . $index . '][vizibil]';
        echo '<label class="pavva-item-visibility">';
        echo '<input type="hidden" name="' . esc_attr( $visibility_input ) . '" value="0">';
        echo '<input type="checkbox" name="' . esc_attr( $visibility_input ) . '" value="1"' . checked( Pavva_W_Data::item_is_visible( $item ), true, false ) . '> ';
        echo '<span>Afișează în pagina produsului</span>';
        echo '</label>';

        echo '<button type="button" class="button-link-delete pavva-delete-row">Șterge</button>';
        echo '</div>';

        echo '<div class="pavva-fields pavva-dimension-main-fields">';
        echo '<div class="pavva-field pavva-field-image">';
        echo '<label>Imagine</label>';
        echo '<div class="pavva-media" data-type="image">';
        echo '<input type="hidden" class="pavva-media-id" name="pavva_dimensiuni[' . esc_attr( $index ) . '][imagine_id]" value="' . esc_attr( $image_id ) . '">';
        echo '<div class="pavva-media-preview">';
        echo $image_url ? '<img src="' . esc_url( $image_url ) . '" alt="">' : '<span>Nicio imagine</span>';
        echo '</div>';
        echo '<button type="button" class="button pavva-select-media">Selectează imagine</button> ';
        echo '<button type="button" class="button-link-delete pavva-remove-media"' . ( $image_url ? '' : ' style="display:none"' ) . '>Elimină</button>';
        echo '</div></div>';
        echo '</div>';

        echo '<div class="pavva-dimension-specs">';
        echo '<div class="pavva-dimension-spec-head">';
        echo '<strong>Specificații</strong>';
        echo '<span>Etichetele și valorile sunt afișate exact în această ordine.</span>';
        echo '</div>';

        echo '<div class="pavva-dimension-spec-list">';
        foreach ( $specs as $spec_i => $spec ) {
            $this->render_dimension_spec_row( $index, $spec_i, $spec );
        }
        echo '</div>';

        echo '<p><button type="button" class="button pavva-add-dimension-spec">+ Adaugă specificație</button></p>';
        echo '</div>';

        echo '</div>';
    }

    private function render_dimension_spec_row( $dim_index, $spec_index, $spec ) {
        $label = isset( $spec['eticheta'] ) ? $spec['eticheta'] : '';
        $value = isset( $spec['valoare'] ) ? $spec['valoare'] : '';

        $base = 'pavva_dimensiuni[' . $dim_index . '][specificatii][' . $spec_index . ']';

        echo '<div class="pavva-dimension-spec-row">';
        echo '<span class="pavva-spec-handle" title="Reordonează">☰</span>';
        echo '<div class="pavva-spec-field">';
        echo '<label>Etichetă</label>';
        echo '<input type="text" class="widefat" name="' . esc_attr( $base . '[eticheta]' ) . '" value="' . esc_attr( $label ) . '" placeholder="Ex.: Dimensiuni mm (Lxl)">';
        echo '</div>';
        echo '<div class="pavva-spec-field">';
        echo '<label>Valoare</label>';
        echo '<input type="text" class="widefat" name="' . esc_attr( $base . '[valoare]' ) . '" value="' . esc_attr( $value ) . '" placeholder="Ex.: 500x100">';
        echo '</div>';
        echo '<button type="button" class="button-link-delete pavva-delete-dimension-spec">Șterge</button>';
        echo '</div>';
    }

    public function box_amenajare( $post ) {
        $this->box_open( 'Adaugă imaginile și titlurile variantelor de amenajare.' );
        $this->render_repeater( 'amenajare', $this->items( $post->ID, self::META_AMEN ), array(
            'imagine_id' => array( 'label'=>'Imagine', 'type'=>'image' ),
            'titlu'      => array( 'label'=>'Titlu', 'type'=>'text', 'placeholder'=>'Ex.: Varianta 1' ),
        ), 'Adaugă variantă' );
        $this->box_close();
    }

    public function box_fise( $post ) {
        $this->box_open( 'Documente PDF afișate în acordeonul „Fișe tehnice”.' );
        $this->render_repeater( 'fise', $this->items( $post->ID, self::META_FISE ), array(
            'denumire' => array( 'label'=>'Denumire document', 'type'=>'text', 'placeholder'=>'Ex.: Fișă tehnică Kombi 4 cm' ),
            'fisier_id'=> array( 'label'=>'Fișier PDF', 'type'=>'file' ),
        ), 'Adaugă document' );
        $this->box_close();
    }

    public function box_declaratii( $post ) {
        $this->box_open( 'Documente PDF afișate în acordeonul „Declarații de performanță”.' );
        $this->render_repeater( 'declaratii', $this->items( $post->ID, self::META_DECL ), array(
            'denumire' => array( 'label'=>'Denumire document', 'type'=>'text', 'placeholder'=>'Ex.: Declarație de performanță Kombi' ),
            'fisier_id'=> array( 'label'=>'Fișier PDF', 'type'=>'file' ),
        ), 'Adaugă document' );
        $this->box_close();
    }

    private function render_repeater( $name, $items, $fields, $button ) {
        echo '<div class="pavva-repeater" data-name="' . esc_attr( $name ) . '">';
        echo '<div class="pavva-repeater-list">';
        foreach ( $items as $i => $item ) $this->render_row( $name, $i, $item, $fields );
        echo '</div>';
        echo '<p><button type="button" class="button button-secondary pavva-add-row">+ ' . esc_html( $button ) . '</button></p>';
        echo '<script type="text/html" class="pavva-row-template">';
        $blank = array();
        foreach ( $fields as $k => $v ) $blank[$k] = '';
        $this->render_row( $name, '{{INDEX}}', $blank, $fields );
        echo '</script></div>';
    }

    private function render_row( $name, $index, $item, $fields ) {
        echo '<div class="pavva-row">';

        $visibility_repeaters = array( 'caracteristici', 'amenajare', 'fise', 'declaratii' );
        $has_visibility = in_array( $name, $visibility_repeaters, true );
        $visible = Pavva_W_Data::item_is_visible( $item );

        echo '<div class="pavva-row-head">';
        echo '<span class="pavva-handle">☰</span><strong>Element</strong>';

        if ( $has_visibility ) {
            $visibility_input = 'pavva_' . $name . '[' . $index . '][vizibil]';
            echo '<label class="pavva-item-visibility">';
            echo '<input type="hidden" name="' . esc_attr( $visibility_input ) . '" value="0">';
            echo '<input type="checkbox" name="' . esc_attr( $visibility_input ) . '" value="1"' . checked( $visible, true, false ) . '> ';
            echo '<span>Afișează în pagina produsului</span>';
            echo '</label>';
        }

        echo '<button type="button" class="button-link-delete pavva-delete-row">Șterge</button>';
        echo '</div>';
        echo '<div class="pavva-fields">';
        foreach ( $fields as $key => $cfg ) {
            $type = $cfg['type'];
            $val  = isset( $item[$key] ) ? $item[$key] : '';
            $input = 'pavva_' . $name . '[' . $index . '][' . $key . ']';
            $label_attr = ! empty( $cfg['label_key'] ) ? ' data-pavva-label-key="' . esc_attr( $cfg['label_key'] ) . '"' : '';
            echo '<div class="pavva-field pavva-field-' . esc_attr( $type ) . '"><label' . $label_attr . '>' . esc_html( $cfg['label'] ) . '</label>';

            if ( 'text' === $type ) {
                echo '<input type="text" class="widefat" name="' . esc_attr( $input ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr( isset($cfg['placeholder']) ? $cfg['placeholder'] : '' ) . '">';
            } elseif ( 'image' === $type ) {
                $id = absint( $val );
                $url = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
                echo '<div class="pavva-media" data-type="image"><input type="hidden" class="pavva-media-id" name="' . esc_attr( $input ) . '" value="' . esc_attr( $id ) . '">';
                echo '<div class="pavva-media-preview">' . ( $url ? '<img src="' . esc_url( $url ) . '" alt="">' : '<span>Nicio imagine</span>' ) . '</div>';
                echo '<button type="button" class="button pavva-select-media">Selectează imagine</button> <button type="button" class="button-link-delete pavva-remove-media"' . ( $url ? '' : ' style="display:none"' ) . '>Elimină</button></div>';
            } elseif ( 'file' === $type ) {
                $id = absint( $val );
                $url = $id ? wp_get_attachment_url( $id ) : '';
                $filename = $id ? basename( get_attached_file( $id ) ) : '';
                echo '<div class="pavva-media" data-type="file"><input type="hidden" class="pavva-media-id" name="' . esc_attr( $input ) . '" value="' . esc_attr( $id ) . '">';
                echo '<div class="pavva-file-preview">' . ( $url ? '<span>' . esc_html( $filename ) . '</span>' : '<span>Niciun fișier</span>' ) . '</div>';
                echo '<button type="button" class="button pavva-select-media">Selectează PDF</button> <button type="button" class="button-link-delete pavva-remove-media"' . ( $url ? '' : ' style="display:none"' ) . '>Elimină</button></div>';
            }
            echo '</div>';
        }
        echo '</div></div>';
    }

    public function save_all( $post_id, $post ) {
        if ( ! isset( $_POST[self::NONCE_NAME] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[self::NONCE_NAME] ) ), self::NONCE_ACTION ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $summary = isset( $_POST['pavva_descriere_sinteza'] )
            ? sanitize_textarea_field( wp_unslash( $_POST['pavva_descriere_sinteza'] ) )
            : '';

        if ( '' !== trim( $summary ) ) update_post_meta( $post_id, self::META_DESCRIERE_SINTEZA, $summary );
        else delete_post_meta( $post_id, self::META_DESCRIERE_SINTEZA );

        $this->save_repeater( $post_id, 'caracteristici', self::META_CARACT, array(
            'denumire'=>'text', 'icon_id'=>'id', 'vizibil'=>'bool'
        ) );
        $color_cols = isset( $_POST['pavva_culori_coloane'] ) ? absint( $_POST['pavva_culori_coloane'] ) : 3;
        if ( ! in_array( $color_cols, array( 3, 4 ), true ) ) $color_cols = 3;
        update_post_meta( $post_id, self::META_CULORI_COLS, $color_cols );

        $this->save_repeater( $post_id, 'culori', self::META_CULORI, array(
            'denumire'=>'text', 'thumbnail_id'=>'id', 'imagine_mare_id'=>'id'
        ) );
        // Dimensiunile folosesc începând cu 4.10.23 rânduri dinamice Etichetă + Valoare.
        // META_DIM_LABELS este păstrat doar pentru compatibilitatea produselor vechi.
        $this->save_dimensions( $post_id );
        $this->save_repeater( $post_id, 'amenajare', self::META_AMEN, array(
            'imagine_id'=>'id', 'titlu'=>'text', 'vizibil'=>'bool'
        ) );
        $this->save_repeater( $post_id, 'fise', self::META_FISE, array(
            'denumire'=>'text', 'fisier_id'=>'id', 'vizibil'=>'bool'
        ) );
        $this->save_repeater( $post_id, 'declaratii', self::META_DECL, array(
            'denumire'=>'text', 'fisier_id'=>'id', 'vizibil'=>'bool'
        ) );
    }

    private function save_dimensions( $post_id ) {
        $raw = isset( $_POST['pavva_dimensiuni'] ) && is_array( $_POST['pavva_dimensiuni'] )
            ? wp_unslash( $_POST['pavva_dimensiuni'] )
            : array();

        $out = array();

        foreach ( $raw as $row ) {
            if ( ! is_array( $row ) ) continue;

            $image_id = isset( $row['imagine_id'] ) ? absint( $row['imagine_id'] ) : 0;
            $visible = isset( $row['vizibil'] ) ? ( '1' === (string) $row['vizibil'] ? 1 : 0 ) : 1;
            $specs_raw = isset( $row['specificatii'] ) && is_array( $row['specificatii'] )
                ? $row['specificatii']
                : array();

            $specs = array();

            foreach ( $specs_raw as $spec ) {
                if ( ! is_array( $spec ) ) continue;

                $label = isset( $spec['eticheta'] ) ? sanitize_text_field( $spec['eticheta'] ) : '';
                $value = isset( $spec['valoare'] ) ? sanitize_text_field( $spec['valoare'] ) : '';

                if ( '' === trim( $label ) && '' === trim( $value ) ) continue;

                $specs[] = array(
                    'eticheta' => $label,
                    'valoare'  => $value,
                );
            }

            if ( $image_id || $specs ) {
                $out[] = array(
                    'imagine_id'   => $image_id,
                    'specificatii' => $specs,
                    'vizibil'      => $visible,
                );
            }
        }

        if ( $out ) update_post_meta( $post_id, self::META_DIM, $out );
        else delete_post_meta( $post_id, self::META_DIM );
    }

    private function save_repeater( $post_id, $name, $meta_key, $schema ) {
        $raw = isset( $_POST['pavva_' . $name] ) && is_array( $_POST['pavva_' . $name] )
            ? wp_unslash( $_POST['pavva_' . $name] )
            : array();

        $out = array();
        foreach ( $raw as $row ) {
            if ( ! is_array( $row ) ) continue;
            $clean = array();
            $has = false;
            foreach ( $schema as $key => $type ) {
                $v = isset( $row[$key] ) ? $row[$key] : '';

                if ( 'id' === $type ) {
                    $v = absint( $v );
                } elseif ( 'bool' === $type ) {
                    $v = ( '1' === (string) $v ) ? 1 : 0;
                } else {
                    $v = sanitize_text_field( $v );
                }

                if ( 'bool' !== $type && '' !== $v && 0 !== $v ) $has = true;
                $clean[$key] = $v;
            }
            if ( $has ) $out[] = $clean;
        }

        if ( $out ) update_post_meta( $post_id, $meta_key, $out );
        else delete_post_meta( $post_id, $meta_key );
    }
}
