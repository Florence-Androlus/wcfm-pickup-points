(function($){
    $(document).ready(function() {

    // AJAX formulaire
    $(document).on('click', '.wcfm_store_branch_edit', function(e){
        e.preventDefault();

        var $btn = $(this);
        var branchData = $btn.data('branch');

        if(!branchData || !branchData.ID) return;
        var branchId = branchData.ID;

        if($('#custom_branch_hours').length === 0){
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'load_pickup_hours_template',
                    branch_id: branchId
                },
                success: function(response){
                    if(response.success && response.data.html){
                        $('#vendor_edit_branch').after(response.data.html);

                        $('#wcfm_vendor_manage_pickup_hours_setting_form').on('submit', function(e){
                            e.preventDefault();
                            var form = $(this);
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: form.serialize(),
                                dataType: 'json',
                                success: function(resp){
                                    if(resp.success){
                                        $('#pickup_hours_message').html('<div class="success">'+resp.data.message+'</div>');
                                    } else {
                                        $('#pickup_hours_message').html('<div class="error">'+resp.data.message+'</div>');
                                    }
                                },
                                error: function(){
                                    $('#pickup_hours_message').html('<div class="error">Erreur lors de l\'enregistrement</div>');
                                }
                            });
                        });
                    }
                }
            });
        }
    });

    // Quand on clique sur "← Back to branch list" 
    $(document).on('click', '.branch-header-wrap .back', function(e) { 
        e.preventDefault(); 
        console.log('Retour à la liste des branches – suppression du bloc d’horaires'); 
        $('#custom_branch_hours').remove(); 
    }); 

    // Gestionnaire pour ajouter un nouveau créneau
    $(document).on('click', '.add_multi_input_block', function() {
        var holder = $(this).closest('.multi_input_holder');
        var day = holder.data('day');
        var index = holder.find('.multi_input_block').length;

        var template = $($('#new-time-slot-template').html());

        // Mettre à jour les name des inputs
        template.find('input[data-name="start"]').attr('name', 'wcfm_pickup_hours[day_times][' + day + '][' + index + '][start]');
        template.find('input[data-name="end"]').attr('name', 'wcfm_pickup_hours[day_times][' + day + '][' + index + '][end]');
        template.find('input[data-name="id"]').attr('name', 'wcfm_pickup_hours[day_times][' + day + '][' + index + '][id]').val(0);

        holder.append(template);
    });

    // Gestionnaire pour le bouton de duplication
    $(document).on('click', '.duplicate-hours-btn', function(e) {
        e.preventDefault();
        var sourceDay = $(this).data('day');
        var days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        
        var $sourceHolder = $('.multi_input_holder[data-day="' + sourceDay + '"]');
        var $sourceSlots = $sourceHolder.find('.multi_input_block');

        if ($sourceSlots.length === 0) {
            alert('Il n\'y a pas d\'horaires définis pour ' + days[sourceDay] + '.');
            return;
        }

        // --- Construction du menu déroulant ---
        var selectHtml = '<div id="duplicate-popup" style="position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 15px rgba(0,0,0,0.2); z-index:9999; border: 1px solid #ccc;">';
        selectHtml += '<p style="margin-bottom:10px;"><strong>Copier les horaires de ' + days[sourceDay] + ' vers :</strong></p>';
        selectHtml += '<select id="destination-select" style="width:100%; padding:8px; margin-bottom:15px;">';
        
        days.forEach(function(dayName, index) {
            if (index != sourceDay) {
                selectHtml += '<option value="' + index + '">' + dayName + '</option>';
            }
        });
        
        selectHtml += '</select>';
        selectHtml += '<div style="text-align:right;">';
        selectHtml += '<button id="cancel-dup" style="margin-right:10px; background:#eee; border:none; padding:5px 10px; cursor:pointer;">Annuler</button>';
        selectHtml += '<button id="confirm-dup" style="background:#0073aa; color:#fff; border:none; padding:5px 10px; cursor:pointer;">Dupliquer</button>';
        selectHtml += '</div></div>';
        selectHtml += '<div id="dup-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:9998;"></div>';

        $('body').append(selectHtml);

        // --- Gestion des clics dans la popup ---
        
        // Fermer
        $('#cancel-dup, #dup-overlay').on('click', function() {
            $('#duplicate-popup, #dup-overlay').remove();
        });

        // Confirmer
        $('#confirm-dup').on('click', function() {
            var destinationDay = $('#destination-select').val();
            var $destinationHolder = $('.multi_input_holder[data-day="' + destinationDay + '"]');

            if ($destinationHolder.length) {
                // 1. Supprimer l'existant
                $destinationHolder.find('.multi_input_block').remove();

                // 2. Copier les créneaux
                $sourceSlots.each(function(index) {
                    var $newSlot = $(this).clone();
                    var startValue = $(this).find('input[data-name="start"]').val();
                    var endValue = $(this).find('input[data-name="end"]').val();
                    
                    var startName = 'wcfm_pickup_hours[day_times][' + destinationDay + '][' + index + '][start]';
                    var endName = 'wcfm_pickup_hours[day_times][' + destinationDay + '][' + index + '][end]';
                    var idName = 'wcfm_pickup_hours[day_times][' + destinationDay + '][' + index + '][id]';

                    $newSlot.find('input[data-name="start"]').attr('name', startName).val(startValue);
                    $newSlot.find('input[data-name="end"]').attr('name', endName).val(endValue);
                    $newSlot.find('input[data-name="id"]').attr('name', idName).val(0);

                    $destinationHolder.append($newSlot);
                });

                // 2. Fermeture popup
                $('#duplicate-popup, #dup-overlay').remove();

                // 3. Animation du bouton de sauvegarde
            var $saveBtn = $('#wcfm_store_hours_setting_save_button');
            var $container = $saveBtn.closest('.wcfm_messages_submit'); // On cible aussi le parent
            
            // On fait défiler la page jusqu'au bouton
            $('html, body').animate({
                scrollTop: $saveBtn.offset().top - 250
            }, 600);

            // Création de l'effet de clignotement
            var flashCount = 0;
            var interval = setInterval(function() {
                if(flashCount % 2 === 0) {
                    // État "Alerte" (Rouge et gros)
                    $saveBtn.css({
                        'background-color': '#d9534f',
                        'border-color': '#d43f3a',
                        'color': '#ffffff',
                        'transform': 'scale(1.15)',
                        'box-shadow': '0 0 20px rgba(217, 83, 79, 0.6)'
                    });
                    $container.css('background-color', 'rgba(217, 83, 79, 0.1)');
                } else {
                    // État "Normal" (Bleu ou couleur d'origine)
                    $saveBtn.css({
                        'background-color': '#0073aa',
                        'border-color': '#006799',
                        'color': '#ffffff',
                        'transform': 'scale(1)',
                        'box-shadow': 'none'
                    });
                    $container.css('background-color', 'transparent');
                }
                
                flashCount++;
                if (flashCount > 10) { // Clignote 5 fois (10 étapes)
                    clearInterval(interval);
                    // Reset propre des styles inline pour laisser le CSS original reprendre la main
                    $saveBtn.attr('style', '');
                    $container.attr('style', '');
                }
            }, 250);

           }

        });
    });

    // Supprimer un créneau
    $(document).on('click', '.remove_multi_input_block', function() {
        var holder = $(this).closest('.multi_input_holder');
        $(this).closest('.multi_input_block').remove(); // Supprime l'élément HTML

        // Réindexer les inputs pour le jour
        var day = holder.data('day');
        holder.find('.multi_input_block').each(function(i){
            $(this).find('input[data-name="start"]').attr('name', 'wcfm_pickup_hours[day_times][' + day + '][' + i + '][start]');
            $(this).find('input[data-name="end"]').attr('name', 'wcfm_pickup_hours[day_times][' + day + '][' + i + '][end]');
            $(this).find('input[data-name="id"]').attr('name', 'wcfm_pickup_hours[day_times][' + day + '][' + i + '][id]');
        });
    });

    /**
     * GESTION DES CATÉGORIES VENDEUR VIA AJAX
     */

    // Tentative de récupération de l'ID
    let vendorId = $('#vendor_id').val() || $('#user_id').val();
    if (!vendorId) {
        const urlParams = new URLSearchParams(window.location.search);
        vendorId = urlParams.get('ID') || urlParams.get('vendor_id');
    }

    // Appel AJAX pour récupérer les catégories déjà sauvées
    $.ajax({
        url: MonPluginData.ajax_url || ajaxurl, 
        type: 'POST',
        data: {
            action: 'get_vendor_categories',
            vendor_id: vendorId
        },
        success: function(response) {
            
            // On récupère les valeurs (si erreur ou vide, on prend un tableau vide)
            const savedValues = (response.success && response.data) ? response.data : [];
            const categories = MonPluginData.categories || [];

            // On injecte le champ seulement si on a la liste des catégories globales
            if (categories.length > 0) {
                injectCategoryField(categories, savedValues);
            }
        },
        error: function(xhr, status, error) {
            console.error("Erreur AJAX lors de la récupération des catégories :", error);
            // En cas d'erreur, on injecte quand même le champ vide
            injectCategoryField(MonPluginData.categories, []);
        }
    });

    function injectCategoryField(allCategories, savedValues) {
        // Sécurité : on s'assure que savedValues est un tableau
        const selectedList = Array.isArray(savedValues) ? savedValues : [];

        let optionsHtml = allCategories.map(cat => {
            const isSelected = selectedList.includes(cat) ? 'selected="selected"' : '';
            return `<option value="${cat}" ${isSelected}>${cat}</option>`;
        }).join('');

        let htmlInject = `
            <p class="store_main_category wcfm_title wcfm_ele">
                <strong>Catégories d'activité (choix multiples)<span class="required">*</span></strong>
                <br>
                <span style="font-weight: normal; font-size: 11px; color: #666; display: block; margin-top: 2px;">
                    Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs activités.
                </span>
            </p>
            <select id="wcfm_store_main_category" 
                    name="wcfm_store_main_category[]" 
                    class="wcfm-select wcfm_ele" 
                    multiple="multiple" 
                    style="margin-bottom: 15px; height: auto; min-height: 100px; width: 60%;">
                ${optionsHtml}
            </select>
        `;

        const $slugInput = $('#store_slug');
        // On vérifie si l'élément n'existe pas déjà pour éviter les doublons
        if ($slugInput.length > 0 && $('#wcfm_store_main_category').length === 0) {
            $slugInput.after(htmlInject);
            
            // Activation de Select2 si présent
            if ($.fn.select2) {
                $('#wcfm_store_main_category').select2({
                    placeholder: "Choisir des activités..."
                });
            }
        }
    }
});
})(jQuery);