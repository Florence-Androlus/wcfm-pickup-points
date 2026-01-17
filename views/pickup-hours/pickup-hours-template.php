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
    <input type="hidden" name="action" value="save_pickup_hours">
    
    <!-- Nonce de sécurité -->
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce('save_pickup_hours_nonce') ); ?>">
    
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
    $fand_jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    foreach(range(0,6) as $fand_day): 
        $fand_day_slots = $fand_hours[$fand_day] ?? [];
    ?>
        <p class="wcfm_store_hours_mon_times wcfm_title wcfm_store_hours_fields wcfm_store_hours_fields_0">
          <strong><?php echo esc_html( $fand_jours[$fand_day] ) ?> : Time Slots</strong>
          <?php 
            // On définit le nom complet avec le namespace
            $fand_func_pro = '\fandWCFMPickupPoints\wcfm_pickup_is_premium_active';
            if ( function_exists($fand_func_pro) && $fand_func_pro() ) : 
          ?>
            <a href="#" class="duplicate-hours-btn" data-day="<?php echo esc_attr( $fand_day ) ?>" title="Dupliquer les horaires">

          <span class="wcfmfa fa-copy"></span>
          </a>
          <?php endif; ?>
        </p>

        <label class="screen-reader-text" for="wcfm_store_hours_mon_times"><?php echo esc_html( $fand_jours[$fand_day] ) ?> : Time Slots</label>
        
        <div class="multi_input_holder" data-day="<?php echo esc_attr( $fand_day ) ?>">
            <?php if(!empty($fand_day_slots)): ?>
                <?php foreach($fand_day_slots as $fand_slot_index => $fand_slot): ?>
                    <div class="multi_input_block ui-sortable-handle">
                        <div class="wcfm_clearfix"></div>
                        
                        <p class="wcfm_store_hours_start wcfm_title wcfm_store_hours_label"><strong>Opening</strong></p>
                        <label class="screen-reader-text">Opening</label>
                        <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" 
                              data-name="start" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fand_day) ?>][<?php echo esc_attr($fand_slot_index) ?>][start]" 
                              value="<?php echo esc_attr($fand_slot['start'] ?? '') ?>">                       
                        <p class="wcfm_store_hours_end wcfm_title wcfm_store_hours_label">
                          <strong>Closing</strong>
                        </p>
                        <label class="screen-reader-text">Closing</label>
                        <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" 
                              data-name="end" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fand_day) ?>][<?php echo esc_attr($fand_slot_index) ?>][end]" 
                              value="<?php echo esc_attr($fand_slot['end'] ?? '') ?>">
                        
                        <span class="multi_input_block_manupulate remove_multi_input_block wcfmfa fa-times-circle"></span>
                        <span class="add_multi_input_block multi_input_block_manupulate wcfmfa fa-plus-circle"></span>

                        <!-- ID du créneau -->
                        <input type="hidden" class="slot-id" 
                              data-name="id" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fand_day) ?>][<?php echo esc_attr($fand_slot_index) ?>][id]" 
                              value="<?php echo !empty($fand_slot['id']) ? intval($fand_slot['id']) : 0 ?>">

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
                          data-name="start" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fand_day) ?>][0][start]" value="">
                    
                    <p class="wcfm_store_hours_end wcfm_title wcfm_store_hours_label"><strong>Closing</strong></p>
                    <label class="screen-reader-text">Closing</label>
                    <input type="time" class="wcfm-text wcfm_store_hours_field multi_input_block_element" 
                          data-name="end" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fand_day) ?>][0][end]" value="">
                    
                    <span class="multi_input_block_manupulate remove_multi_input_block wcfmfa fa-times-circle"></span>
                    <span class="add_multi_input_block multi_input_block_manupulate wcfmfa fa-plus-circle"></span>

                    <input type="hidden" class="slot-id" 
                          data-name="id" name="wcfm_pickup_hours[day_times][<?php echo esc_attr($fand_day) ?>][0][id]" value="0">
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
      <?php
            if ( function_exists($fand_func_pro) && $fand_func_pro() ) : 
      ?>
			<div class="wcfm_pickup_settings_heading"><h2>Mode vacances</h2></div>
			<div class="wcfm_clearfix"></div>
						
			<div class="store_address">
        <p class="wcfm_vacation_mode wcfm_title checkbox_title wcfm_ele">
          <strong>Activer le mode vacances</strong>
        </p>
        <label class="screen-reader-text" for="wcfm_vacation_mode">Activer le mode vacances</label>
        <input type="checkbox" id="wcfm_vacation_mode" name="wcfm_vacation_mode" class="wcfm-checkbox wcfm_ele" value="yes">
        <p class="wcfm_disable_vacation_purchase wcfm_title wcfm_ele">
          <strong>Désactiver l'achat pendant les vacances</strong>
        </p>
        <label class="screen-reader-text" for="wcfm_disable_vacation_purchase">Désactiver l'achat pendant les vacances</label>
        <input type="checkbox" id="wcfm_disable_vacation_purchase" name="wcfm_disable_vacation_purchase" class="wcfm-checkbox wcfm_ele" value="yes">
        <p class="wcfm_vacation_mode_type wcfm_title wcfm_ele">
          <strong>Vacation Type</strong>
        </p>
        <label class="screen-reader-text" for="wcfm_vacation_mode_type">Vacation Type</label>
        <select id="wcfm_vacation_mode_type" name="wcfm_vacation_mode_type" class="wcfm-select wcfm_ele">
          <option value="instant" selected="selected">Instantly Close</option>
          <option value="date_wise">Date wise close</option>
        </select>
        <p class="wcfm_vacation_start_date wcfm_title wcfm_ele date_wise_vacation_ele wcfm_ele_hide">
          <strong>Message</strong>
        </p>
        <label class="screen-reader-text" for="wcfm_vacation_start_date">Message</label>
        <input type="text" id="wcfm_vacation_start_date" name="wcfm_vacation_start_date" class="wcfm-text wcfm_ele date_wise_vacation_ele wcfm_ele_hide hasDatepicker" value="" placeholder="Message ... YYYY-MM-DD">
        <p class="wcfm_vacation_end_date wcfm_title wcfm_ele date_wise_vacation_ele wcfm_ele_hide">
          <strong>Jusqu'à</strong>
        </p>
        <label class="screen-reader-text" for="wcfm_vacation_end_date">Jusqu'à</label>
        <input type="text" id="wcfm_vacation_end_date" name="wcfm_vacation_end_date" class="wcfm-text wcfm_ele date_wise_vacation_ele wcfm_ele_hide hasDatepicker" value="" placeholder="À ... YYYY-MM-DD">
        <p class="wcfm_vacation_mode_msg wcfm_title wcfm_ele">
          <strong>Message de vacances</strong>
        </p>
        <label class="screen-reader-text" for="wcfm_vacation_mode_msg">Message de vacances</label>
        <textarea id="wcfm_vacation_mode_msg" name="wcfm_vacation_mode_msg" class="wcfm-textarea wcfm_ele" placeholder="" rows="2" cols="20"></textarea>						
      </div>

			<div class="wcfm-clearfix"></div>
      <div class="wcfm-message" tabindex="-1" style="display: none;"></div>
      <div class="wcfm-clearfix"></div>
      <div class="wcfm_messages_submit">
        <input type="submit" name="save-data" value="Mise à jour " id="wcfm_store_vacation_setting_save_button" class="wcfm_submit_button">
      </div>
    <?php endif; ?>
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

