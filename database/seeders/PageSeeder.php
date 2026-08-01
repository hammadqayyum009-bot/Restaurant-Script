<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'meta_description' => 'Terms and conditions for using our website and ordering food online.',
                'content' => <<<'HTML'
                    <p>Welcome to our website. These Terms &amp; Conditions govern your use of our website and any orders you place with us for food delivery or pickup. By accessing this website or placing an order, you agree to be bound by these terms in full.</p>

                    <h2>1. Orders &amp; Acceptance</h2>
                    <p>All orders placed through this website are subject to acceptance and availability. We reserve the right to refuse or cancel any order at our discretion, including in cases of suspected fraud, pricing errors, or unavailability of items. You will be notified promptly if any item in your order cannot be fulfilled.</p>

                    <h2>2. Pricing &amp; Payment</h2>
                    <ul>
                        <li>All prices displayed are in the currency shown at checkout and are inclusive of applicable taxes unless stated otherwise.</li>
                        <li>A delivery fee may apply depending on your order type and location.</li>
                        <li>Payment can be made via cash on delivery or card on delivery, as selected during checkout.</li>
                        <li>We reserve the right to change menu prices at any time without prior notice.</li>
                    </ul>

                    <h2>3. Delivery &amp; Pickup</h2>
                    <p>Estimated delivery and preparation times are provided as a guide only and may vary due to weather, traffic, order volume, or other circumstances beyond our control. Please ensure the address and contact details provided at checkout are accurate and complete.</p>

                    <h2>4. Food Quality &amp; Allergens</h2>
                    <p>We prepare our dishes fresh using quality ingredients. If you have any food allergies or dietary requirements, please contact us before ordering. While we take reasonable care, our kitchen handles a variety of ingredients including nuts, dairy, gluten and shellfish, and we cannot guarantee any dish is completely free from allergens.</p>

                    <h2>5. Cancellations</h2>
                    <p>Orders may be cancelled free of charge before preparation has begun. Once preparation has started, cancellations may not be possible. Please see our Refund &amp; Cancellation Policy for full details.</p>

                    <h2>6. Website Use</h2>
                    <p>You agree to use this website only for lawful purposes and in a way that does not infringe the rights of others or restrict their use of the website. You must not misuse this website by knowingly introducing viruses or other malicious material.</p>

                    <h2>7. Limitation of Liability</h2>
                    <p>To the fullest extent permitted by law, we shall not be liable for any indirect or consequential loss arising from the use of this website or the placing of an order, including but not limited to loss of profits or data.</p>

                    <h2>8. Changes to These Terms</h2>
                    <p>We may update these Terms &amp; Conditions from time to time. Continued use of the website after changes are posted constitutes acceptance of the revised terms.</p>

                    <h2>9. Contact Us</h2>
                    <p>If you have any questions about these Terms &amp; Conditions, please reach out to us using the contact details in the footer of this website.</p>
                HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'meta_description' => 'How we collect, use and protect your personal information.',
                'content' => <<<'HTML'
                    <p>We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, store and protect your personal information when you use our website or place an order with us.</p>

                    <h2>1. Information We Collect</h2>
                    <ul>
                        <li>Contact details such as your name, phone number, email address and delivery address.</li>
                        <li>Order history and preferences.</li>
                        <li>Technical information such as IP address, browser type, and pages visited, collected automatically via cookies.</li>
                    </ul>

                    <h2>2. How We Use Your Information</h2>
                    <p>We use your information to process and deliver your orders, communicate with you regarding your order status, improve our menu and services, and respond to enquiries or feedback. We do not sell your personal information to third parties.</p>

                    <h2>3. Sharing of Information</h2>
                    <p>We may share limited information with third parties strictly to fulfil your order, such as delivery riders or payment processors. These parties are only permitted to use your data for the purpose of completing your order.</p>

                    <h2>4. Data Retention</h2>
                    <p>We retain your personal information only for as long as necessary to fulfil the purposes outlined in this policy, or as required by law for accounting and tax purposes.</p>

                    <h2>5. Data Security</h2>
                    <p>We implement reasonable technical and organisational measures to protect your personal data against unauthorised access, loss, or misuse. However, no method of transmission over the internet is completely secure.</p>

                    <h2>6. Your Rights</h2>
                    <p>You have the right to request access to, correction of, or deletion of your personal data held by us. To exercise these rights, please contact us using the details provided in the footer.</p>

                    <h2>7. Children's Privacy</h2>
                    <p>Our website and services are not directed at children under the age of 16, and we do not knowingly collect personal information from children.</p>

                    <h2>8. Changes to This Policy</h2>
                    <p>We may update this Privacy Policy periodically. Any changes will be posted on this page with an updated revision date.</p>
                HTML,
            ],
            [
                'slug' => 'cookies-policy',
                'title' => 'Cookies Policy',
                'meta_description' => 'Information about how and why we use cookies on this website.',
                'content' => <<<'HTML'
                    <p>This Cookies Policy explains what cookies are, how we use them on this website, and the choices available to you regarding their use.</p>

                    <h2>1. What Are Cookies?</h2>
                    <p>Cookies are small text files placed on your device when you visit a website. They are widely used to make websites work more efficiently, remember your preferences, and provide information to website owners.</p>

                    <h2>2. How We Use Cookies</h2>
                    <ul>
                        <li><strong>Essential Cookies</strong> &mdash; required for core website functionality such as maintaining your shopping cart contents and session while you browse our menu.</li>
                        <li><strong>Preference Cookies</strong> &mdash; remember choices you make, such as your preferred order type, to improve your experience on return visits.</li>
                        <li><strong>Analytics Cookies</strong> &mdash; help us understand how visitors interact with our website so we can improve our menu and service.</li>
                    </ul>

                    <h2>3. Cart &amp; Session Cookies</h2>
                    <p>Our shopping cart relies on session cookies to remember the items you have added while you browse the menu. Without these cookies, the cart feature would not function correctly.</p>

                    <h2>4. Managing Cookies</h2>
                    <p>Most web browsers allow you to control cookies through their settings, including blocking or deleting cookies. Please note that disabling essential cookies may affect the functionality of our website, including the ability to place an order.</p>

                    <h2>5. Third-Party Cookies</h2>
                    <p>Some pages on our website may include content from third parties (such as maps or social media widgets) which may set their own cookies. We do not control these cookies and recommend reviewing the relevant third party's cookie policy.</p>

                    <h2>6. Updates to This Policy</h2>
                    <p>We may update this Cookies Policy from time to time to reflect changes in technology, law, or our operations.</p>
                HTML,
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund & Cancellation Policy',
                'meta_description' => 'Our policy on order cancellations, refunds and complaints.',
                'content' => <<<'HTML'
                    <p>Customer satisfaction is important to us. This policy explains how order cancellations, refunds and complaints are handled.</p>

                    <h2>1. Order Cancellations</h2>
                    <ul>
                        <li>Orders can be cancelled free of charge before the kitchen begins preparation.</li>
                        <li>Once preparation has started, we may not be able to cancel the order as ingredients have already been used.</li>
                        <li>To cancel an order, please contact us immediately by phone or WhatsApp with your order number.</li>
                    </ul>

                    <h2>2. Refund Eligibility</h2>
                    <p>Refunds or replacements may be issued in the following circumstances:</p>
                    <ul>
                        <li>An item was missing from your delivered order.</li>
                        <li>The food received was significantly different from what was ordered.</li>
                        <li>The order arrived in an unacceptable condition due to an error on our part.</li>
                        <li>The order was significantly delayed beyond a reasonable timeframe without prior communication.</li>
                    </ul>

                    <h2>3. How to Request a Refund</h2>
                    <p>Please contact us within 24 hours of receiving your order, quoting your order number and a description of the issue, along with a photo where applicable. We aim to review and respond to all refund requests within 2 business days.</p>

                    <h2>4. Non-Refundable Situations</h2>
                    <p>We are unable to offer refunds in situations such as a change of mind after the order has been prepared, incorrect address or contact details provided by the customer, or delays caused by circumstances outside our control (e.g. severe weather or traffic disruptions).</p>

                    <h2>5. Refund Method</h2>
                    <p>Approved refunds will be issued using the original payment method where possible, or as store credit toward a future order, at the customer's preference.</p>

                    <h2>6. Contact Us</h2>
                    <p>For any cancellation or refund request, please reach out via the phone number, email or WhatsApp link provided in the footer of this website.</p>
                HTML,
            ],
            [
                'slug' => 'faq',
                'title' => 'Frequently Asked Questions',
                'meta_description' => 'Answers to common questions about ordering, delivery and our menu.',
                'content' => <<<'HTML'
                    <h2>How do I place an order?</h2>
                    <p>Browse our menu, click "Add to Cart" on the dishes you'd like, then review your cart and proceed to checkout. Fill in your delivery details and confirm your order &mdash; it's that simple.</p>

                    <h2>What areas do you deliver to?</h2>
                    <p>We currently deliver across the local area surrounding our restaurant. If you're unsure whether we deliver to your address, please contact us before placing your order.</p>

                    <h2>How long does delivery take?</h2>
                    <p>Most orders are prepared and delivered within 30&ndash;60 minutes, depending on order volume, distance and traffic conditions.</p>

                    <h2>Can I pick up my order instead of delivery?</h2>
                    <p>Yes! Simply select "Pickup" as your order type at checkout and collect your order from our restaurant once it's ready.</p>

                    <h2>What payment methods do you accept?</h2>
                    <p>We currently accept cash on delivery and card on delivery. Additional online payment options may be added in the future.</p>

                    <h2>Is your food halal?</h2>
                    <p>Yes, all our meat is sourced from certified halal suppliers.</p>

                    <h2>Can I customise my order (e.g. spice level, no onions)?</h2>
                    <p>Absolutely. You can add special instructions in the "Order Notes" field at checkout, and our kitchen will do its best to accommodate your request.</p>

                    <h2>How can I remove an item from my cart?</h2>
                    <p>Open your cart from the icon in the top right of the page, then click "Remove" next to any item you'd like to delete, or adjust the quantity using the plus/minus buttons.</p>

                    <h2>Do you cater for events or large groups?</h2>
                    <p>Yes, we offer catering for events and gatherings of all sizes. Please contact us directly to discuss your requirements and get a custom quote.</p>

                    <h2>How do I contact you about an issue with my order?</h2>
                    <p>Please reach out to us as soon as possible via phone, email, or WhatsApp using the details in the footer, along with your order number.</p>
                HTML,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
