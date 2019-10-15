<?php
/* @var $config EE_Multisite_Config */
?>
<div class="padding">
    <h4>
        <?php _e('Multisite Settings', 'event_espresso'); ?>
    </h4>
    <table class="form-table">
        <tbody>

        <tr>
            <th><?php _e("Reset Multisite Settings?", 'event_espresso'); ?></th>
            <td>
                <?php echo EEH_Form_Fields::select(__('Reset Multisite Settings?', 'event_espresso'), 0, $yes_no_values, 'reset_multisite', 'reset_multisite'); ?><br/>
                <span class="description">
                        <?php _e('Set to \'Yes\' and then click \'Save\' to confirm reset all basic and advanced Event Espresso Multisite settings to their plugin defaults.', 'event_espresso'); ?>
                    </span>
            </td>
        </tr>

        </tbody>
    </table>

</div>

<input type='hidden' name="return_action" value="<?php echo $return_action ?>">

