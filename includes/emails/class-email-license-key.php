<?php
// File: includes/emails/class-email-license-key.php
if ( ! defined( 'ABSPATH' ) ) exit;

class Plinkly_Email_License_Key extends WC_Email {

    public function __construct() {
        $this->id             = 'plinkly_license_key';
        $this->customer_email = true;

        $this->title       = __( 'PlinkLy – License Key', 'plinkly-smart-cta-buttons' );
        $this->description = __( 'Sends the license key after a subscription is activated or renewed.', 'plinkly-smart-cta-buttons' );

        // يمكن تغييرهما من WooCommerce > Settings > Emails
        $this->subject  = __( '[PlinkLy] Your License Key', 'plinkly-smart-cta-buttons' );
        $this->heading  = __( 'Your PlinkLy License Key', 'plinkly-smart-cta-buttons' );

        // مسارات القوالب النسخة HTML والنصيّة
        $this->template_html  = 'emails/plinkly-license-key.php';
        $this->template_plain = ''; // اتركه فارغًا إن لم ترد نسخة نصيّة

        parent::__construct();

        // Event مخصَّص سنُطلقه من subscription-hooks
        add_action( 'plinkly/send_license_email', [ $this, 'trigger' ], 10, 4 );
    }

    /** يُرسل البريد فعليًا */
    public function trigger( $license_key, $plan_label, $expires_at, $email_to ) {

        $this->recipient = $email_to;

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $this->object = [
            'license_key' => $license_key,
            'plan_label'  => $plan_label,
            'expires_at'  => $expires_at,
            'manage_url'  => admin_url( 'admin.php?page=go2pick-cta-license' ),
            'docs_url'    => 'https://plink.ly/docs/',
            'support_url' => 'https://plink.ly/support/',
        ];

        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }

    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            $this->object,
            '',
            PLINKLY_PATH . 'templates/'   // ↖️ ثابت PLINKLY_PATH سنُعرّفه بعد قليل
        );
    }

    public function get_content_plain() {
        // إن أردت نسخة نصيّة
        return '';
    }
}
