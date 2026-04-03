<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                [
                    'type'    => $template['type'],
                    'channel' => $template['channel'],
                    'locale'  => $template['locale'],
                ],
                [
                    'subject' => $template['subject'] ?? null,
                    'body'    => $template['body'],
                ],
            );
        }
    }

    private function getTemplates(): array
    {
        return [
            // ══════════════════════════════════════════════════════════
            // WELCOME
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'welcome', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Welcome to ZabuniLink!',
                'body' => '<p class="greeting">Welcome to ZabuniLink, {{user_name}}!</p>
<p class="text">Your account has been created successfully. ZabuniLink connects you with the latest tenders across Tanzania.</p>
<div class="card">
    <div class="card-row"><span class="card-label">Name</span><span class="card-value">{{user_name}}</span></div>
    <div class="card-row"><span class="card-label">Email</span><span class="card-value">{{user_email}}</span></div>
    <div class="card-row"><span class="card-label">Business</span><span class="card-value">{{business_name}}</span></div>
</div>
<p class="text">Browse tenders, save favorites, set up notifications, and subscribe for more access.</p>
<div class="btn-center"><a href="{{app_url}}/tenders" class="btn">Browse Tenders</a></div>',
            ],
            [
                'type' => 'welcome', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Karibu ZabuniLink!',
                'body' => '<p class="greeting">Karibu ZabuniLink, {{user_name}}!</p>
<p class="text">Akaunti yako imeundwa kwa mafanikio. ZabuniLink inakuunganisha na zabuni za hivi karibuni nchini Tanzania.</p>
<div class="card">
    <div class="card-row"><span class="card-label">Jina</span><span class="card-value">{{user_name}}</span></div>
    <div class="card-row"><span class="card-label">Barua pepe</span><span class="card-value">{{user_email}}</span></div>
    <div class="card-row"><span class="card-label">Biashara</span><span class="card-value">{{business_name}}</span></div>
</div>
<p class="text">Tazama zabuni, hifadhi unazozipenda, weka arifa, na ujisajili kwa huduma zaidi.</p>
<div class="btn-center"><a href="{{app_url}}/tenders" class="btn">Tazama Zabuni</a></div>',
            ],
            [
                'type' => 'welcome', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'Welcome to ZabuniLink, {{user_name}}! Browse tenders, save favorites, and subscribe to unlock more features. Start now at {{app_url}}',
            ],
            [
                'type' => 'welcome', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'Karibu ZabuniLink, {{user_name}}! Tazama zabuni, hifadhi unazozipenda, na ujisajili kupata huduma zaidi. Anza sasa {{app_url}}',
            ],

            // ══════════════════════════════════════════════════════════
            // PASSWORD RESET
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'password_reset', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'ZabuniLink — Password Reset Code',
                'body' => '<p class="greeting">Password Reset Request</p>
<p class="text">Hello {{user_name}}, we received a request to reset your ZabuniLink password. Use the code below:</p>
<div class="card" style="text-align:center;">
    <p style="font-size:36px; font-weight:700; letter-spacing:8px; color:#10b981; margin:12px 0;">{{otp}}</p>
    <p style="font-size:13px; color:#71717a;">This code expires in 15 minutes.</p>
</div>
<p class="text">If you didn\'t request this, you can safely ignore this email.</p>
<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">For security, never share this code with anyone.</p>',
            ],
            [
                'type' => 'password_reset', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'ZabuniLink — Nambari ya Kubadilisha Nenosiri',
                'body' => '<p class="greeting">Ombi la Kubadilisha Nenosiri</p>
<p class="text">Habari {{user_name}}, tumepokea ombi la kubadilisha nenosiri lako la ZabuniLink. Tumia nambari hii:</p>
<div class="card" style="text-align:center;">
    <p style="font-size:36px; font-weight:700; letter-spacing:8px; color:#10b981; margin:12px 0;">{{otp}}</p>
    <p style="font-size:13px; color:#71717a;">Nambari hii itaisha baada ya dakika 15.</p>
</div>
<p class="text">Ikiwa hukuomba hili, puuza barua pepe hii.</p>
<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">Kwa usalama, usishiriki nambari hii na mtu yeyote.</p>',
            ],
            [
                'type' => 'password_reset', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Your password reset code is {{otp}}. It expires in 15 minutes. Do not share this code.',
            ],
            [
                'type' => 'password_reset', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Nambari yako ya kubadilisha nenosiri ni {{otp}}. Itaisha baada ya dakika 15. Usishiriki nambari hii.',
            ],

            // ══════════════════════════════════════════════════════════
            // SUBSCRIPTION CONFIRMED
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'subscription_confirmed', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Subscription Activated — {{plan_name}} Plan',
                'body' => '<p class="greeting">Subscription Activated!</p>
<p class="text">Hello {{user_name}}, your subscription has been activated successfully.</p>
<div class="card">
    <div class="card-row"><span class="card-label">Plan</span><span class="card-value">{{plan_name}}</span></div>
    <div class="card-row"><span class="card-label">Billing Cycle</span><span class="card-value">{{billing_cycle}}</span></div>
    <div class="card-row"><span class="card-label">Amount</span><span class="card-value">TZS {{amount}}</span></div>
    <div class="card-row"><span class="card-label">Start Date</span><span class="card-value">{{start_date}}</span></div>
    <div class="card-row"><span class="card-label">Expires</span><span class="card-value">{{end_date}}</span></div>
</div>
<div class="btn-center"><a href="{{app_url}}/subscription" class="btn">View Subscription</a></div>',
            ],
            [
                'type' => 'subscription_confirmed', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Usajili Umewashwa — Mpango wa {{plan_name}}',
                'body' => '<p class="greeting">Usajili Umewashwa!</p>
<p class="text">Habari {{user_name}}, usajili wako umewashwa kwa mafanikio.</p>
<div class="card">
    <div class="card-row"><span class="card-label">Mpango</span><span class="card-value">{{plan_name}}</span></div>
    <div class="card-row"><span class="card-label">Mzunguko wa Malipo</span><span class="card-value">{{billing_cycle}}</span></div>
    <div class="card-row"><span class="card-label">Kiasi</span><span class="card-value">TZS {{amount}}</span></div>
    <div class="card-row"><span class="card-label">Tarehe ya Kuanza</span><span class="card-value">{{start_date}}</span></div>
    <div class="card-row"><span class="card-label">Inaisha</span><span class="card-value">{{end_date}}</span></div>
</div>
<div class="btn-center"><a href="{{app_url}}/subscription" class="btn">Tazama Usajili</a></div>',
            ],
            [
                'type' => 'subscription_confirmed', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Your {{plan_name}} plan is now active! Valid until {{end_date}}. Enjoy your subscription.',
            ],
            [
                'type' => 'subscription_confirmed', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Mpango wako wa {{plan_name}} umewashwa! Unaisha {{end_date}}. Furahia usajili wako.',
            ],

            // ══════════════════════════════════════════════════════════
            // PAYMENT RECEIPT
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'payment_receipt', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Payment Receipt — {{reference}}',
                'body' => '<p class="greeting">Payment Receipt</p>
<p class="text">Hello {{user_name}}, thank you for your payment. Here is your receipt:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Reference</span><span class="card-value">{{reference}}</span></div>
    <div class="card-row"><span class="card-label">Amount</span><span class="card-value" style="font-size:16px; color:#10b981;">TZS {{amount}}</span></div>
    <div class="card-row"><span class="card-label">Method</span><span class="card-value">{{method}}</span></div>
    <div class="card-row"><span class="card-label">Transaction ID</span><span class="card-value">{{transaction_id}}</span></div>
    <div class="card-row"><span class="card-label">Date</span><span class="card-value">{{date}}</span></div>
</div>
<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">Keep this email as your payment record.</p>',
            ],
            [
                'type' => 'payment_receipt', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Risiti ya Malipo — {{reference}}',
                'body' => '<p class="greeting">Risiti ya Malipo</p>
<p class="text">Habari {{user_name}}, asante kwa malipo yako. Hii ni risiti yako:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Kumbukumbu</span><span class="card-value">{{reference}}</span></div>
    <div class="card-row"><span class="card-label">Kiasi</span><span class="card-value" style="font-size:16px; color:#10b981;">TZS {{amount}}</span></div>
    <div class="card-row"><span class="card-label">Njia</span><span class="card-value">{{method}}</span></div>
    <div class="card-row"><span class="card-label">Nambari ya Muamala</span><span class="card-value">{{transaction_id}}</span></div>
    <div class="card-row"><span class="card-label">Tarehe</span><span class="card-value">{{date}}</span></div>
</div>
<hr class="divider" />
<p class="text" style="font-size:13px; color:#a1a1aa;">Hifadhi barua pepe hii kama kumbukumbu ya malipo yako.</p>',
            ],
            [
                'type' => 'payment_receipt', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Payment of TZS {{amount}} received. Ref: {{reference}}. Thank you!',
            ],
            [
                'type' => 'payment_receipt', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Malipo ya TZS {{amount}} yamepokelewa. Kumb: {{reference}}. Asante!',
            ],

            // ══════════════════════════════════════════════════════════
            // TRIAL STARTED
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'trial_started', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your Free Trial Has Started!',
                'body' => '<p class="greeting">Your Free Trial Has Started!</p>
<p class="text">Hello {{user_name}}, your free Basic plan trial is now active.</p>
<div class="card">
    <div class="card-row"><span class="card-label">Plan</span><span class="card-value">{{plan_name}} (Free Trial)</span></div>
    <div class="card-row"><span class="card-label">Started</span><span class="card-value">{{start_date}}</span></div>
    <div class="card-row"><span class="card-label">Expires</span><span class="card-value">{{end_date}}</span></div>
    <div class="card-row"><span class="card-label">Duration</span><span class="card-value">{{trial_days}} days</span></div>
</div>
<p class="text">When your trial ends, subscribe to continue using premium features.</p>
<div class="btn-center"><a href="{{app_url}}/subscription" class="btn">View Plans</a></div>',
            ],
            [
                'type' => 'trial_started', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Jaribio Lako la Bure Limeanza!',
                'body' => '<p class="greeting">Jaribio Lako la Bure Limeanza!</p>
<p class="text">Habari {{user_name}}, jaribio lako la bure la mpango wa Basic limewashwa.</p>
<div class="card">
    <div class="card-row"><span class="card-label">Mpango</span><span class="card-value">{{plan_name}} (Jaribio la Bure)</span></div>
    <div class="card-row"><span class="card-label">Ilianza</span><span class="card-value">{{start_date}}</span></div>
    <div class="card-row"><span class="card-label">Inaisha</span><span class="card-value">{{end_date}}</span></div>
    <div class="card-row"><span class="card-label">Muda</span><span class="card-value">siku {{trial_days}}</span></div>
</div>
<p class="text">Jaribio linapoisha, jisajili kuendelea kutumia huduma za ziada.</p>
<div class="btn-center"><a href="{{app_url}}/subscription" class="btn">Tazama Mipango</a></div>',
            ],
            [
                'type' => 'trial_started', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Your {{trial_days}}-day free trial has started! Enjoy Basic plan features until {{end_date}}.',
            ],
            [
                'type' => 'trial_started', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Jaribio lako la bure la siku {{trial_days}} limeanza! Furahia huduma za Basic hadi {{end_date}}.',
            ],

            // ══════════════════════════════════════════════════════════
            // TRIAL EXPIRING
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'trial_expiring', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your Free Trial Expires in {{days_left}} Days',
                'body' => '<p class="greeting">Your Free Trial Expires Soon</p>
<p class="text">Hello {{user_name}}, your free trial expires in <strong>{{days_left}} day(s)</strong> on {{end_date}}.</p>
<p class="text">Subscribe to a plan before your trial ends to continue accessing tender listings and other features.</p>
<div class="btn-center"><a href="{{app_url}}/subscription" class="btn">Subscribe Now</a></div>',
            ],
            [
                'type' => 'trial_expiring', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Jaribio Lako la Bure Linaisha kwa Siku {{days_left}}',
                'body' => '<p class="greeting">Jaribio Lako la Bure Linakaribia Kuisha</p>
<p class="text">Habari {{user_name}}, jaribio lako la bure linaisha kwa <strong>siku {{days_left}}</strong> tarehe {{end_date}}.</p>
<p class="text">Jisajili kwenye mpango kabla jaribio lako halijisha ili kuendelea kupata zabuni na huduma nyingine.</p>
<div class="btn-center"><a href="{{app_url}}/subscription" class="btn">Jisajili Sasa</a></div>',
            ],
            [
                'type' => 'trial_expiring', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Your free trial expires in {{days_left}} day(s). Subscribe now to keep access: {{app_url}}/subscription',
            ],
            [
                'type' => 'trial_expiring', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Jaribio lako la bure linaisha kwa siku {{days_left}}. Jisajili sasa: {{app_url}}/subscription',
            ],

            // ══════════════════════════════════════════════════════════
            // SUBSCRIPTION EXPIRING
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'subscription_expiring', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your {{plan_name}} Plan Expires in {{days_left}} Days',
                'body' => '<p class="greeting">Your Subscription Expires Soon</p>
<p class="text">Hello {{user_name}}, your <strong>{{plan_name}}</strong> plan expires in <strong>{{days_left}} day(s)</strong> on {{end_date}}.</p>
<div class="card">
    <div class="card-row"><span class="card-label">Plan</span><span class="card-value">{{plan_name}}</span></div>
    <div class="card-row"><span class="card-label">Billing Cycle</span><span class="card-value">{{billing_cycle}}</span></div>
    <div class="card-row"><span class="card-label">Expires</span><span class="card-value" style="color:#ef4444; font-weight:700;">{{end_date}}</span></div>
</div>
<p class="text">Renew your subscription to continue uninterrupted access to ZabuniLink.</p>
<div class="btn-center"><a href="{{app_url}}/subscription" class="btn">Renew Subscription</a></div>',
            ],
            [
                'type' => 'subscription_expiring', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Mpango Wako wa {{plan_name}} Unaisha kwa Siku {{days_left}}',
                'body' => '<p class="greeting">Usajili Wako Unakaribia Kuisha</p>
<p class="text">Habari {{user_name}}, mpango wako wa <strong>{{plan_name}}</strong> unaisha kwa <strong>siku {{days_left}}</strong> tarehe {{end_date}}.</p>
<div class="card">
    <div class="card-row"><span class="card-label">Mpango</span><span class="card-value">{{plan_name}}</span></div>
    <div class="card-row"><span class="card-label">Mzunguko</span><span class="card-value">{{billing_cycle}}</span></div>
    <div class="card-row"><span class="card-label">Inaisha</span><span class="card-value" style="color:#ef4444; font-weight:700;">{{end_date}}</span></div>
</div>
<p class="text">Huisha usajili wako ili kuendelea kupata huduma za ZabuniLink bila kukatika.</p>
<div class="btn-center"><a href="{{app_url}}/subscription" class="btn">Huisha Usajili</a></div>',
            ],
            [
                'type' => 'subscription_expiring', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Your {{plan_name}} plan expires in {{days_left}} day(s). Renew to avoid interruption: {{app_url}}/subscription',
            ],
            [
                'type' => 'subscription_expiring', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Mpango wako wa {{plan_name}} unaisha kwa siku {{days_left}}. Huisha ili kuendelea: {{app_url}}/subscription',
            ],

            // ══════════════════════════════════════════════════════════
            // NEW TENDER
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'new_tender', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'New Tender: {{tender_title}}',
                'body' => '<p class="greeting">New Tender Alert</p>
<p class="text">Hello {{user_name}}, a new tender matching your preferences has been posted:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Tender</span><span class="card-value">{{tender_title}}</span></div>
    <div class="card-row"><span class="card-label">Organization</span><span class="card-value">{{organization}}</span></div>
    <div class="card-row"><span class="card-label">Deadline</span><span class="card-value">{{deadline}}</span></div>
</div>
<div class="btn-center"><a href="{{tender_link}}" class="btn">View Tender</a></div>',
            ],
            [
                'type' => 'new_tender', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Zabuni Mpya: {{tender_title}}',
                'body' => '<p class="greeting">Arifa ya Zabuni Mpya</p>
<p class="text">Habari {{user_name}}, zabuni mpya inayolingana na mapendeleo yako imetangazwa:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Zabuni</span><span class="card-value">{{tender_title}}</span></div>
    <div class="card-row"><span class="card-label">Taasisi</span><span class="card-value">{{organization}}</span></div>
    <div class="card-row"><span class="card-label">Mwisho</span><span class="card-value">{{deadline}}</span></div>
</div>
<div class="btn-center"><a href="{{tender_link}}" class="btn">Tazama Zabuni</a></div>',
            ],
            [
                'type' => 'new_tender', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: New tender "{{tender_title}}" by {{organization}}. Deadline: {{deadline}}. View: {{tender_link}}',
            ],
            [
                'type' => 'new_tender', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Zabuni mpya "{{tender_title}}" na {{organization}}. Mwisho: {{deadline}}. Tazama: {{tender_link}}',
            ],

            // ══════════════════════════════════════════════════════════
            // TENDER UPDATE
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'tender_update', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Tender Updated: {{tender_title}}',
                'body' => '<p class="greeting">Tender Updated</p>
<p class="text">Hello {{user_name}}, a tender you saved has been updated:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Tender</span><span class="card-value">{{tender_title}}</span></div>
</div>
<p class="text">Please review the changes to stay up to date.</p>
<div class="btn-center"><a href="{{tender_link}}" class="btn">View Tender</a></div>',
            ],
            [
                'type' => 'tender_update', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Zabuni Imesasishwa: {{tender_title}}',
                'body' => '<p class="greeting">Zabuni Imesasishwa</p>
<p class="text">Habari {{user_name}}, zabuni uliyohifadhi imesasishwa:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Zabuni</span><span class="card-value">{{tender_title}}</span></div>
</div>
<p class="text">Tafadhali kagua mabadiliko ili kuwa na taarifa za hivi karibuni.</p>
<div class="btn-center"><a href="{{tender_link}}" class="btn">Tazama Zabuni</a></div>',
            ],
            [
                'type' => 'tender_update', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Tender "{{tender_title}}" has been updated. Review changes: {{tender_link}}',
            ],
            [
                'type' => 'tender_update', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Zabuni "{{tender_title}}" imesasishwa. Kagua: {{tender_link}}',
            ],

            // ══════════════════════════════════════════════════════════
            // DEADLINE REMINDER
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'deadline_reminder', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Deadline Reminder: {{tender_title}}',
                'body' => '<p class="greeting">Deadline Reminder</p>
<p class="text">Hello {{user_name}}, the tender <strong>"{{tender_title}}"</strong> closes on <strong>{{deadline}}</strong>. Don\'t miss the deadline!</p>
<div class="btn-center"><a href="{{tender_link}}" class="btn">View Tender</a></div>',
            ],
            [
                'type' => 'deadline_reminder', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Ukumbusho wa Mwisho: {{tender_title}}',
                'body' => '<p class="greeting">Ukumbusho wa Mwisho</p>
<p class="text">Habari {{user_name}}, zabuni <strong>"{{tender_title}}"</strong> inafungwa tarehe <strong>{{deadline}}</strong>. Usikose!</p>
<div class="btn-center"><a href="{{tender_link}}" class="btn">Tazama Zabuni</a></div>',
            ],
            [
                'type' => 'deadline_reminder', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Reminder — "{{tender_title}}" closes on {{deadline}}. Don\'t miss it! {{tender_link}}',
            ],
            [
                'type' => 'deadline_reminder', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Ukumbusho — "{{tender_title}}" inafungwa {{deadline}}. Usikose! {{tender_link}}',
            ],

            // ══════════════════════════════════════════════════════════
            // NEW APPLICATION
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'new_application', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'New Application: {{tender_title}}',
                'body' => '<p class="greeting">New Application Received</p>
<p class="text">Hello {{owner_name}}, your tender has received a new application:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Tender</span><span class="card-value">{{tender_title}}</span></div>
    <div class="card-row"><span class="card-label">Applicant</span><span class="card-value">{{applicant_name}}</span></div>
    <div class="card-row"><span class="card-label">Business</span><span class="card-value">{{applicant_business}}</span></div>
    <div class="card-row"><span class="card-label">Email</span><span class="card-value">{{applicant_email}}</span></div>
    <div class="card-row"><span class="card-label">Applied At</span><span class="card-value">{{applied_at}}</span></div>
</div>
<div class="btn-center"><a href="{{application_link}}" class="btn">Review Application</a></div>',
            ],
            [
                'type' => 'new_application', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Maombi Mapya: {{tender_title}}',
                'body' => '<p class="greeting">Maombi Mapya Yamepokelewa</p>
<p class="text">Habari {{owner_name}}, zabuni yako imepokea maombi mapya:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Zabuni</span><span class="card-value">{{tender_title}}</span></div>
    <div class="card-row"><span class="card-label">Mwombaji</span><span class="card-value">{{applicant_name}}</span></div>
    <div class="card-row"><span class="card-label">Biashara</span><span class="card-value">{{applicant_business}}</span></div>
    <div class="card-row"><span class="card-label">Barua pepe</span><span class="card-value">{{applicant_email}}</span></div>
    <div class="card-row"><span class="card-label">Tarehe</span><span class="card-value">{{applied_at}}</span></div>
</div>
<div class="btn-center"><a href="{{application_link}}" class="btn">Kagua Maombi</a></div>',
            ],
            [
                'type' => 'new_application', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: New application for "{{tender_title}}" from {{applicant_name}}. Review it at {{application_link}}',
            ],
            [
                'type' => 'new_application', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Maombi mapya ya "{{tender_title}}" kutoka {{applicant_name}}. Kagua: {{application_link}}',
            ],

            // ══════════════════════════════════════════════════════════
            // APPLICATION STATUS
            // ══════════════════════════════════════════════════════════
            [
                'type' => 'application_status', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Application {{status}}: {{tender_title}}',
                'body' => '<p class="greeting">Application Status Update</p>
<p class="text">Hello {{applicant_name}}, the status of your application has been updated:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Tender</span><span class="card-value">{{tender_title}}</span></div>
    <div class="card-row"><span class="card-label">Status</span><span class="card-value"><span class="badge badge-{{status}}">{{status}}</span></span></div>
</div>
<div class="btn-center"><a href="{{tender_link}}" class="btn">View Details</a></div>',
            ],
            [
                'type' => 'application_status', 'channel' => 'email', 'locale' => 'sw',
                'subject' => 'Hali ya Maombi {{status}}: {{tender_title}}',
                'body' => '<p class="greeting">Sasishi ya Hali ya Maombi</p>
<p class="text">Habari {{applicant_name}}, hali ya maombi yako imesasishwa:</p>
<div class="card">
    <div class="card-row"><span class="card-label">Zabuni</span><span class="card-value">{{tender_title}}</span></div>
    <div class="card-row"><span class="card-label">Hali</span><span class="card-value"><span class="badge badge-{{status}}">{{status}}</span></span></div>
</div>
<div class="btn-center"><a href="{{tender_link}}" class="btn">Tazama Maelezo</a></div>',
            ],
            [
                'type' => 'application_status', 'channel' => 'sms', 'locale' => 'en',
                'subject' => null,
                'body' => 'ZabuniLink: Your application for "{{tender_title}}" has been {{status}}. View: {{tender_link}}',
            ],
            [
                'type' => 'application_status', 'channel' => 'sms', 'locale' => 'sw',
                'subject' => null,
                'body' => 'ZabuniLink: Maombi yako ya "{{tender_title}}" yamekuwa {{status}}. Tazama: {{tender_link}}',
            ],
        ];
    }
}
