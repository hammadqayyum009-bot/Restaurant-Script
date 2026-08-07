@extends('admin.layouts.app')

@section('title', 'Email templates')

@section('content')
    <form method="POST" action="{{ route('admin.email.templates.save') }}">
        @csrf @method('PUT')

        <div class="a-card">
            <p style="margin:0;">
                Each template is wrapped in your restaurant's branded email layout automatically.
                Drop the placeholders listed under each box into the subject or body and they are replaced when the email is sent.
            </p>
        </div>

        @foreach ($templates as $key => $template)
            <div class="a-card">
                <div class="a-card-head"><h2>{{ $template['label'] }}</h2></div>

                <div class="a-field">
                    <label for="tpl_{{ $key }}_subject">Subject</label>
                    <input type="text" id="tpl_{{ $key }}_subject" name="tpl_{{ $key }}_subject" class="a-input"
                           value="{{ old('tpl_'.$key.'_subject', $mailer->templateSubject($key)) }}">
                </div>

                <div class="a-field">
                    <label for="tpl_{{ $key }}_body">Message</label>
                    <textarea id="tpl_{{ $key }}_body" name="tpl_{{ $key }}_body" class="a-textarea tall">{{ old('tpl_'.$key.'_body', $mailer->templateBody($key)) }}</textarea>
                    <span class="a-hint">HTML is allowed.</span>
                    <div class="a-tokens">
                        @foreach ($template['vars'] as $var)
                            <span class="a-token">&#123;&#123;{{ $var }}&#125;&#125;</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="a-form-actions">
            <button type="submit" class="a-btn">Save all templates</button>
        </div>
    </form>
@endsection
