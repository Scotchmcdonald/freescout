{!! strip_tags($auto_reply_message) !!}
@if (\App\Models\Option::get('email_branding'))

-----------------------------------------------------------
{!! __('Support powered by :app_name — Free open source help desk & shared mailbox', ['app_name' => \Config::get('app.name')]) !!}
@endif
