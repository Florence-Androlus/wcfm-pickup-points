(function($){
    $(document).ready(function() {
        // On détecte quelle variable est disponible
        const config = window.config || window.FAND_PICKUP_DATA;

        if (!config) {
            console.error("Aucune configuration trouvée pour le plugin Pickup.");
            return;
        }
        
        // AJAX formulaire
        $(document).on('click', '.wcfm_store_branch_edit', function(e){
                e.preventDefault();

                var $btn = $(this);
                var branchData = $btn.data('branch');

                if(!branchData || !branchData.ID) return;
                var branchId = branchData.ID;

                if($('#custom_branch_hours').length === 0){
                    $.ajax({
                        url: config.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'load_pickup_hours_template',
                            branch_id: branchId,
                            _wpnonce: config.loadPickupNonce
                        },
                        success: function(response){
                            if(response.success && response.data.html){
                                $('#vendor_edit_branch').after(response.data.html);

                                $('#wcfm_vendor_manage_pickup_hours_setting_form').on('submit', function(e){
                                    e.preventDefault();
                                    var $form = $(this);

                                    var data = {
                                        action: 'save_pickup_hours',
                                        _wpnonce: config.savePickupNonce,
                                        branch_id: $form.find('[name="branch_id"]').val(),
                                        wcfm_pickup_hours: JSON.stringify({ day_times: getPickupHoursData() })
                                    };

                                    $.ajax({
                                        url: config.ajax_url,
                                        type: 'POST',
                                        data: data,
                                        dataType: 'json',
                                        success: function(resp){
                                            if(resp.success){
                                                $('#pickup_hours_message').html('<div class="success">'+resp.data.message+'</div>');
                                            } else {
                                                $('#pickup_hours_message').html('<div class="error">'+resp.data.message+'</div>');
                                            }
                                        },
                                        error: function(xhr){
                                            $('#pickup_hours_message').html('<div class="error">Erreur AJAX: '+xhr.responseText+'</div>');
                                        }
                                    });
                                });
                            }
                        }
                    });
            }
        });

        function getPickupHoursData() {
            var day_times = {};

            $('.multi_input_holder').each(function() {
                var day_index = $(this).data('day'); // data-day="0..6"
                day_times[day_index] = [];

                $(this).find('.multi_input_block').each(function(i) {
                    var start = $(this).find('input[data-name="start"]').val();
                    var end   = $(this).find('input[data-name="end"]').val();
                    var id    = $(this).find('input[data-name="id"]').val() || 0;

                    if (start || end) {
                        day_times[day_index].push({ start: start, end: end, id: id });
                    }
                });
            });

            return day_times;
        }

        // Quand on clique sur "← Back to branch list" 
        $(document).on('click', '.branch-header-wrap .back', function(e) { 
            e.preventDefault(); 
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
            url: config.ajax_url, // On utilise config partout
            type: 'POST',
            data: {
                action: 'get_vendor_categories',
                vendor_id: vendorId,
                security: config.getCategoriesNonce // Assurez-vous que ce nom correspond au PHP
            },
            success: function(response) {
                const savedValues = (response.success && response.data) ? response.data : [];
                // On récupère les catégories ou un tableau vide pour éviter le crash
                const categories = config.categories || [];

                if (categories.length > 0) {
                    injectCategoryField(categories, savedValues);
                }
            },
            error: function(xhr, status, error) {
                console.error("Erreur AJAX lors de la récupération des catégories :", error);
                // SECURITÉ : On passe un tableau vide [] si les catégories sont absentes
                const categories = (config && config.categories) ? config.categories : [];
                injectCategoryField(categories, []);
            }
        });

        function injectCategoryField(allCategories, savedValues) {
            // 1. Double sécurité pour éviter le TypeError 'map'
            if (!allCategories || !Array.isArray(allCategories)) {
                console.warn("injectCategoryField: allCategories n'est pas un tableau valide.");
                return; 
            }

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