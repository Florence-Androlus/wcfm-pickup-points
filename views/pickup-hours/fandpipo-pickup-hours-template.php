<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<div id="custom_branch_hours">

  <h2>Horaires quotidiens d'ouverture et de fermeture</h2>
  
  <!-- DIV pour afficher les messages AJAX -->
  <div id="pickup_hours_message"></div>
  
  <form id="wcfm_vendor_manage_pickup_hours_setting_form" class="wcfm">
    <!-- Action AJAX pour WordPress -->
    <input type="hidden" name="action" value="fandpipo_save_pickup_hours">
    
    <!-- Nonce de sécurité -->
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce('fandpipo_save_pickup_hours_nonce') ); ?>">
    
    <!-- Branch ID -->
    <input type="hidden" name="branch_id" value="<?php echo esc_attr($branch_id); ?>">
		
    <div class="wcfm_clearfix"></div>
    <div class="wcfm-clearfix"></div>
			<div class="wcfm_messages_submit">
				<input type="submit" name="save-data" value="Mise à jour " id="wcfm_store_hours_setting_save_button" class="wcfm_submit_button">
			</div>
    <div class="wcfm-clearfix"></div>
    <div class="wcfm_clearfix"></div>

    <?php 
    $fandpipo_jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    foreach(range(0,6) as $fandpipo_day): 
        $fandpipo_day_slots = $fand_hours[$fandpipo_day] ?? [];
    ?>
        <p class="wcfm_store_hours_mon_times wcfm_title wcfm_store_hours_fields wcfm_store_hours_fields_0">
          <strong><?php echo esc_html( $fandpipo_jours[$fandpipo_day] ) ?> : Time Slots</strong>
        </p>

        <label class="screen-reader-text" for="wcfm_store_hours_mon_times"><?php echo esc_html( $fandpipo_jours[$fandpipo_day] ) ?> : Time Slots</label>
        
        <div class="multi_input_holder" data-fandpipo_day="<?php echo esc_attr( $fandpipo_day ) ?>">
            <?php if(!empty($fandpipo_day_slots)): ?>
                <?php foreach($fandpipo_day_slots as $fandpipo_slot_index => $fandpipo_slot): ?>
                    <div class="multi_input_block ui-sortable-handle">
                        <div class="wcfm_clearfix"></div>
                        
                        <p class="wcfm_store_hours_start wcfm_title wcfm_store_hours_label"><strong>Opening</strong></p>
                        <label class="screen-reader-text">Opening</label>
                        <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" 
                              data-name="start" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fandpipo_day) ?>][<?php echo esc_attr($fandpipo_slot_index) ?>][start]" 
                              value="<?php echo esc_attr($fandpipo_slot['start'] ?? '') ?>">                       
                        <p class="wcfm_store_hours_end wcfm_title wcfm_store_hours_label">
                          <strong>Closing</strong>
                        </p>
                        <label class="screen-reader-text">Closing</label>
                        <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" 
                              data-name="end" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fandpipo_day) ?>][<?php echo esc_attr($fandpipo_slot_index) ?>][end]" 
                              value="<?php echo esc_attr($fandpipo_slot['end'] ?? '') ?>">
                        
                        <span class="multi_input_block_manupulate remove_multi_input_block wcfmfa fa-times-circle"></span>
                        <span class="add_multi_input_block multi_input_block_manupulate wcfmfa fa-plus-circle"></span>

                        <!-- ID du créneau -->
                        <input type="hidden" class="slot-id" 
                              data-name="id" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fandpipo_day) ?>][<?php echo esc_attr($fandpipo_slot_index) ?>][id]" 
                              value="<?php echo !empty($fandpipo_slot['id']) ? intval($fandpipo_slot['id']) : 0 ?>">

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Bloc vide par défaut -->
                <div class="multi_input_block ui-sortable-handle">
                    <span class="wcfmfa fa-arrows-alt wcfm_multiblock_sortable"></span>
                    <div class="wcfm_clearfix"></div>
                    
                    <p class="wcfm_store_hours_start wcfm_title wcfm_store_hours_label"><strong>Opening</strong></p>
                    <label class="screen-reader-text">Opening</label>
                    <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" 
                          data-name="start" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fandpipo_day) ?>][0][start]" value="">
                    
                    <p class="wcfm_store_hours_end wcfm_title wcfm_store_hours_label"><strong>Closing</strong></p>
                    <label class="screen-reader-text">Closing</label>
                    <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" 
                          data-name="end" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fandpipo_day) ?>][0][end]" value="">
                    
                    <span class="multi_input_block_manupulate remove_multi_input_block wcfmfa fa-times-circle"></span>
                    <span class="add_multi_input_block multi_input_block_manupulate wcfmfa fa-plus-circle"></span>

                    <input type="hidden" class="slot-id" 
                          data-name="id" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fandpipo_day) ?>][0][id]" value="0">
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
			<div class="wcfm-clearfix"></div>
			<div class="wcfm-message" tabindex="-1" style="display: none;"></div>
			<div class="wcfm-clearfix"></div>
			<div class="wcfm_messages_submit">
				<input type="submit" name="save-data" value="Mise à jour " id="wcfm_store_hours_setting_save_button" class="wcfm_submit_button">
			</div>
			<div class="wcfm-clearfix"></div>
      <div class="wcfm_clearfix"></div>
      
      <div class="wcfm-clearfix"></div>
														
  </form>
</div>

<!-- Template pour clonage JS -->
<template id="new-time-slot-template">
    <div class="multi_input_block ui-sortable-handle">
        <div class="wcfm_clearfix"></div>

        <p class="wcfm_store_hours_start wcfm_title wcfm_store_hours_label"><strong>Opening</strong></p>
        <label class="screen-reader-text">Opening</label>
        <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" data-name="start">

        <p class="wcfm_store_hours_end wcfm_title wcfm_store_hours_label"><strong>Closing</strong></p>
        <label class="screen-reader-text">Closing</label>
        <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" data-name="end">

        <span class="multi_input_block_manupulate remove_multi_input_block wcfmfa fa-times-circle"></span>
        <span class="add_multi_input_block multi_input_block_manupulate wcfmfa fa-plus-circle"></span>

        <input type="hidden" class="slot-id" data-name="id" value="0">
    </div>
</template>

