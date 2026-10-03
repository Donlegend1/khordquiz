// Copied from the KhordQuiz app (src/lib/terms-of-service.ts). Keep the two in sync.
import { LEGAL_CONTACT_EMAIL, list, p } from '@/data/legal/helpers';

const INTRO = [
  p('These Terms of Service (“Terms”) govern your access to and use of KhordQuiz (the “App”), operated by KingsleyKhordpiano, LLC (“Company,” “we,” “us,” or “our”).'),
  p('By creating an account, purchasing a subscription or lifetime plan, accessing, or using KhordQuiz, you agree to these Terms. If you do not agree to these Terms, please do not use the App.'),
];

const SECTIONS = [
  {
    title: '1. About KhordQuiz',
    blocks: [
      p('KhordQuiz is an ear-training and music-learning application designed to help musicians, instrumentalists, and other music learners develop their ability to hear and identify musical elements.'),
      p('The App may provide exercises and learning experiences relating to musical notes, chords, melodic lines, and other aspects of musical ear training.'),
      p('KhordQuiz is an educational tool. It is not a substitute for formal music instruction, professional training, or individualized instruction.'),
    ],
  },
  {
    title: '2. Eligibility',
    blocks: [
      p('KhordQuiz is intended for musicians and music learners and may be used by people of different ages, including children.'),
      p('If you are under the age of majority in your country, you may use KhordQuiz only with the involvement and permission of your parent or legal guardian where required by applicable law.'),
      p('Where applicable law requires parental or guardian authorization before a child may create an account, make a purchase, or have personal information collected or processed, the required authorization must be obtained before the applicable activity occurs.'),
      p('Parents and legal guardians are responsible for supervising a child’s use of KhordQuiz where appropriate.'),
    ],
  },
  {
    title: '3. Accounts',
    blocks: [
      p('Certain features of KhordQuiz require an account.'),
      p('You may create or access an account through the authentication methods made available by the Company, including Google Sign-In and Sign in with Apple.'),
      p('You agree to provide accurate information when creating your account and to keep your account information reasonably current.'),
      p('You are responsible for maintaining the security of your account and for activities carried out through your account, except where such activity results from circumstances outside your reasonable control.'),
      p('You must not use another person’s account without authorization.'),
    ],
  },
  {
    title: '4. Google Sign-In',
    blocks: [
      p('KhordQuiz may allow you to create or access your account using Google Sign-In.'),
      p('When you use Google Sign-In, Google may provide KhordQuiz with information necessary to authenticate and establish your account.'),
      p('Depending on the configuration and permissions used by KhordQuiz, this may include information such as your Google account’s unique identifier, email address, email verification status, name, profile information, or other information made available through the authentication process.'),
      p('Google’s authentication services are also subject to Google’s applicable terms and policies.'),
    ],
  },
  {
    title: '5. Sign in with Apple',
    blocks: [
      p('KhordQuiz may allow you to create or access your account using Sign in with Apple.'),
      p('When you use Sign in with Apple, Apple may provide KhordQuiz with information necessary to authenticate and establish your account.'),
      p('Depending on your choices and the configuration of the Sign in with Apple service, this may include a unique Apple-provided user identifier, your name, and a verified email address.'),
      p('You may also choose to hide your personal email address through Apple’s private email relay functionality. In that case, KhordQuiz may receive an Apple-provided private relay email address rather than your personal email address.'),
      p('Apple’s authentication services are subject to Apple’s applicable terms and policies.'),
    ],
  },
  {
    title: '6. Paid Plans',
    blocks: [
      p('KhordQuiz is a paid application and may offer:'),
      list('monthly subscription plans;', 'annual subscription plans; and', 'a one-time lifetime purchase.'),
      p('The price, currency, billing period, and other applicable purchase terms will be displayed before you complete a purchase.'),
      p('Prices may vary by country, currency, taxes, payment method, platform, or other factors applicable to the transaction.'),
    ],
  },
  {
    title: '7. Subscriptions',
    blocks: [
      p('Monthly and annual subscriptions provide access to the applicable KhordQuiz features for the subscription period purchased.'),
      p('Where a subscription is configured for recurring billing, it will renew for the applicable subscription period unless it is cancelled before the next renewal date.'),
      p('You may cancel a subscription using the cancellation method provided by KhordQuiz or, where applicable, through the payment provider or platform through which you purchased the subscription.'),
      p('Cancellation normally prevents future charges but does not automatically create a right to a refund for a payment that has already been processed.'),
      p('The cancellation and renewal terms presented to you at the time of purchase form part of your agreement with the Company.'),
      p('Where a subscription is purchased through Apple In-App Purchase, subscription management, renewal, cancellation, and applicable refund processes may be administered through Apple’s platform and are also subject to Apple’s applicable terms and policies.'),
    ],
  },
  {
    title: '8. Lifetime Plan',
    blocks: [
      p('Where available, KhordQuiz may offer a lifetime purchase for a one-time payment.'),
      p('A lifetime purchase provides access to the applicable features of KhordQuiz for the lifetime of the KhordQuiz service, subject to these Terms.'),
      p('“Lifetime” refers to the lifetime of the service and does not constitute a guarantee that KhordQuiz will operate indefinitely or permanently.'),
      p('The Company may modify, discontinue, replace, or substantially change the service in accordance with these Terms and applicable law.'),
    ],
  },
  {
    title: '9. Payments',
    blocks: [
      p('Payments may be processed through third-party payment providers or platforms, including:'),
      list('Stripe;', 'PayPal; and', 'Apple In-App Purchase.'),
      p('When you make a purchase, you authorize the applicable payment provider or platform to process the transaction using the payment method you select.'),
      p('The Company’s handling of payment-related information is described in the KhordQuiz Privacy Policy.'),
      p('Your use of Stripe, PayPal, or Apple In-App Purchase may also be subject to the applicable terms and policies of those providers or platforms.'),
      p('For purchases made through Apple In-App Purchase, Apple may process the payment, billing, subscription, and related transaction information. The purchase may also be subject to Apple’s applicable payment and subscription terms.'),
    ],
  },
  {
    title: '10. Refund Policy',
    blocks: [
      p('Except where required by applicable law or the applicable platform’s mandatory policies, payments for KhordQuiz are non-refundable.'),
      p('The Company may provide a refund in the following circumstances:'),
      list('you were charged twice for the same purchase due to a duplicate payment; or', 'a technical error attributable to the Company resulted in an incorrect charge.'),
      p(`Refund requests may be submitted to ${LEGAL_CONTACT_EMAIL}.`),
      p('Refund requests should include sufficient information for the Company to identify the relevant transaction.'),
      p('Where a transaction was processed by a third-party payment provider or platform, the processing of the refund may also be subject to that provider’s or platform’s procedures.'),
      p('Nothing in this section limits any mandatory refund, cancellation, withdrawal, or other consumer right that cannot legally be excluded.'),
    ],
  },
  {
    title: '11. Use of KhordQuiz',
    blocks: [
      p('You may use KhordQuiz for its intended purpose of personal music learning and ear training.'),
      p('You may not:'),
      list('hack or crack KhordQuiz;', 'bypass or attempt to bypass authentication, payment, access controls, or other security mechanisms;', 'circumvent restrictions on paid features;', 'attempt to obtain unauthorized access to KhordQuiz systems, accounts, databases, or infrastructure;', 'exploit a vulnerability for unauthorized access or benefit;', 'reverse engineer, decompile, disassemble, or otherwise attempt to derive the source code of KhordQuiz except to the extent such activity cannot legally be restricted;', 'interfere with the normal operation or security of KhordQuiz;', 'use automated methods to interfere with or abuse the service; or', 'assist another person in carrying out any prohibited activity.'),
    ],
  },
  {
    title: '12. Account Suspension and Termination',
    blocks: [
      p('The Company may suspend or terminate your account if the Company reasonably determines that you have hacked, cracked, compromised, circumvented, or attempted to circumvent the security, access controls, payment controls, or technical protections of KhordQuiz.'),
      p('The Company may also take technical measures necessary to protect KhordQuiz, its users, and its systems from unauthorized access or abuse.'),
      p('Except where otherwise required by applicable law, termination of an account does not automatically entitle the user to a refund of amounts previously paid.'),
      p('Nothing in this section limits rights that cannot legally be waived or excluded.'),
    ],
  },
  {
    title: '13. Intellectual Property',
    blocks: [
      p('KhordQuiz and all content and materials made available through the App are owned by or licensed to KingsleyKhordpiano, LLC.'),
      p('This includes, without limitation:'),
      list('software and source code;', 'ear-training exercises;', 'musical exercises;', 'instructional materials;', 'audio;', 'text;', 'graphics;', 'user interfaces;', 'designs;', 'logos;', 'trademarks;', 'branding;', 'databases; and', 'other materials contained in or associated with KhordQuiz.'),
      p('Except as expressly permitted by these Terms or applicable law, you may not reproduce, copy, distribute, modify, publish, sell, license, publicly perform, publicly display, commercially exploit, or create derivative works from KhordQuiz or its content without the Company’s prior written permission.'),
      p('Your purchase of a subscription or lifetime plan gives you a limited, non-exclusive, non-transferable right to access and use KhordQuiz for its intended purposes. It does not transfer ownership of any Company intellectual property to you.'),
    ],
  },
  {
    title: '14. No User-Generated Content',
    blocks: [
      p('KhordQuiz does not currently provide users with a feature for uploading or publishing user-generated content such as photographs, videos, documents, posts, or messages.'),
      p('Accordingly, you do not receive any license to publish content through KhordQuiz on behalf of other users, and the Company does not claim ownership of content that users upload because the App does not currently provide such an upload feature.'),
    ],
  },
  {
    title: '15. Educational Nature of the Service',
    blocks: [
      p('KhordQuiz is designed to support musical ear training and learning.'),
      p('The Company does not guarantee that your use of KhordQuiz will produce a particular level of musical ability or hearing skill.'),
      p('Your results may depend on factors including your musical background, practice habits, prior experience, frequency of use, and individual learning characteristics.'),
    ],
  },
  {
    title: '16. Third-Party Services',
    blocks: [
      p('KhordQuiz uses third-party services, which may include:'),
      list('Google for authentication;', 'Apple for authentication and, where applicable, payment processing;', 'Stripe for payment processing; and', 'PayPal for payment processing.'),
      p('Third-party services may operate under their own terms, privacy policies, and other conditions.'),
      p('The Company is not responsible for the independent operation, availability, security, or policies of third-party services, except to the extent liability cannot legally be excluded.'),
    ],
  },
  {
    title: '17. Service Availability',
    blocks: [
      p('We aim to keep KhordQuiz available and functional, but we do not guarantee that the App will always be uninterrupted, error-free, or available.'),
      p('KhordQuiz may occasionally be unavailable because of maintenance, technical problems, security incidents, third-party service interruptions, network problems, platform restrictions, or circumstances outside the Company’s reasonable control.'),
      p('We may update, modify, add, remove, suspend, or discontinue features of KhordQuiz from time to time.'),
      p('KhordQuiz is intended to be accessible to users in multiple countries and regions. However, availability may be subject to applicable laws, regulations, sanctions, export controls, platform requirements, and technical limitations in particular locations.'),
    ],
  },
  {
    title: '18. Disclaimer of Warranties',
    blocks: [
      p('To the maximum extent permitted by applicable law, KhordQuiz is provided on an “as available” and “as is” basis.'),
      p('To the maximum extent permitted by law, the Company disclaims warranties that:'),
      list('KhordQuiz will always be uninterrupted;', 'KhordQuiz will always be error-free;', 'every feature will remain available indefinitely;', 'KhordQuiz will meet every user’s particular requirements; or', 'the App will produce a particular learning outcome.'),
      p('Nothing in these Terms excludes a warranty or statutory protection that cannot legally be excluded.'),
    ],
  },
  {
    title: '19. Limitation of Liability',
    blocks: [
      p('To the maximum extent permitted by applicable law, KingsleyKhordpiano, LLC and its officers, members, employees, contractors, affiliates, and service providers will not be liable for indirect, incidental, special, consequential, exemplary, or punitive damages arising from or relating to your use of or inability to use KhordQuiz.'),
      p('To the maximum extent permitted by applicable law, the Company’s aggregate liability arising out of or relating to KhordQuiz or these Terms will be limited to the amount you paid to the Company for KhordQuiz during the twelve months preceding the event giving rise to the claim.'),
      p('This limitation does not apply to liability that cannot legally be limited or excluded.'),
    ],
  },
  {
    title: '20. Indemnification',
    blocks: [
      p('To the extent permitted by applicable law, you agree to indemnify and hold harmless KingsleyKhordpiano, LLC and its officers, members, employees, contractors, and agents from claims, liabilities, damages, losses, and expenses arising from your unlawful use of KhordQuiz, your violation of these Terms, or your violation of another person’s rights.'),
      p('This provision does not require you to indemnify the Company for liability caused by the Company’s own conduct where such indemnification cannot legally be required.'),
    ],
  },
  {
    title: '21. Applicable Law and Disputes',
    blocks: [
      p('These Terms do not designate the law of a particular U.S. state or country as the exclusive governing law.'),
      p('Your use of KhordQuiz and these Terms may be subject to the laws and regulations applicable to you and to the Company, including mandatory consumer-protection and other non-waivable legal requirements.'),
      p('Nothing in these Terms is intended to deprive you of any mandatory legal right or protection available to you in your jurisdiction.'),
      p('Any dispute arising out of or relating to KhordQuiz or these Terms may be brought before a court or other forum having lawful jurisdiction over the dispute, subject to any mandatory rights concerning the location or forum in which a claim may be brought.'),
    ],
  },
  {
    title: '22. Changes to These Terms',
    blocks: [
      p('We may update these Terms from time to time.'),
      p('If we make material changes, we may notify you through KhordQuiz, by email, or by another reasonable method.'),
      p('The updated Terms will become effective on the date specified in the updated Terms.'),
      p('Your continued use of KhordQuiz after the effective date of updated Terms constitutes acceptance of the updated Terms to the extent permitted by applicable law.'),
    ],
  },
  {
    title: '23. Severability',
    blocks: [
      p('If any provision of these Terms is found to be invalid, unlawful, or unenforceable, that provision will be enforced to the maximum extent permitted by law, and the remaining provisions will remain in effect.'),
    ],
  },
  {
    title: '24. Entire Agreement',
    blocks: [
      p('These Terms and the KhordQuiz Privacy Policy constitute the agreement between you and the Company concerning your use of KhordQuiz, except where additional terms are expressly presented to you for particular features or transactions.'),
    ],
  },
  {
    title: '25. Contact',
    blocks: [
      p('Questions concerning these Terms may be directed to:'),
    ],
  },
];

export const TERMS_OF_SERVICE = { effectiveDate: 'October 2, 2026', intro: INTRO, sections: SECTIONS, title: 'Terms of Service' };
