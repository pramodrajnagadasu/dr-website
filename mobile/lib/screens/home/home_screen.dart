import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../api/api_client.dart';
import '../../state/auth_state.dart';
import '../../theme/app_theme.dart';

const _services = [
  ('Primary Care & Preventive Medicine', Icons.health_and_safety),
  ('Diabetes & Metabolic Disorders', Icons.bloodtype),
  ('Hypertension & Cardiovascular Risk', Icons.favorite),
  ('Respiratory Conditions', Icons.air),
  ('Infectious Diseases', Icons.coronavirus),
  ('Thyroid & Endocrine Disorders', Icons.biotech),
  ('Geriatric Care', Icons.elderly),
  ("Women's Health", Icons.female),
  ('Minor Procedures & Wound Care', Icons.medical_services),
];

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 220,
            backgroundColor: AppColors.navy,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(colors: [AppColors.navy, AppColors.blue], begin: Alignment.topLeft, end: Alignment.bottomRight),
                ),
                padding: const EdgeInsets.fromLTRB(20, 60, 20, 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    const Text('Dr. Prasanna Kumar K', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800)),
                    const SizedBox(height: 6),
                    const Text('Expert primary care & comprehensive medical care — available online.',
                        style: TextStyle(color: Colors.white70, fontSize: 13)),
                    const SizedBox(height: 14),
                    Row(children: [
                      ElevatedButton.icon(
                        onPressed: () => context.push('/book'),
                        icon: const Icon(Icons.calendar_month, size: 18),
                        label: const Text('Book Appointment'),
                      ),
                      const SizedBox(width: 10),
                      OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: const BorderSide(color: Colors.white70)),
                        onPressed: () => launchUrl(Uri.parse('https://wa.me/919538099960'), mode: LaunchMode.externalApplication),
                        icon: const Icon(Icons.chat, size: 18),
                        label: const Text('WhatsApp'),
                      ),
                    ]),
                  ],
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                _SectionTitle('About Us'),
                const Text(
                  'A premier comprehensive medical care clinic in Bengaluru — from diagnosis to '
                  'treatment for acute and chronic medical conditions, with 15+ years of clinical experience.',
                  style: TextStyle(color: AppColors.gray),
                ),
                const SizedBox(height: 24),
                _SectionTitle('Our Specialised Services'),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: _services
                      .map((s) => _ServiceChip(label: s.$1, icon: s.$2))
                      .toList(),
                ),
                const SizedBox(height: 24),
                _SectionTitle('Meet Our Doctor'),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const Text('Dr. Prasanna Kumar K', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: AppColors.navy)),
                      const Text('MBBS, AFIH', style: TextStyle(color: AppColors.blue, fontWeight: FontWeight.w600, fontSize: 13)),
                      const SizedBox(height: 10),
                      const Text(
                        'Occupational Health Physician with 15+ years of clinical experience, specialising '
                        'in industrial and occupational medicine, primary care, and chronic disease management.',
                        style: TextStyle(color: AppColors.gray),
                      ),
                      const SizedBox(height: 14),
                      ElevatedButton(onPressed: () => context.push('/book'), child: const Text('Book an Appointment')),
                    ]),
                  ),
                ),
                const SizedBox(height: 24),
                _SectionTitle('Get in Touch'),
                const _ContactForm(),
                const SizedBox(height: 24),
                Consumer<AuthState>(builder: (context, auth, _) {
                  return OutlinedButton.icon(
                    onPressed: () => context.push('/portal'),
                    icon: const Icon(Icons.folder_shared_outlined),
                    label: Text(auth.status == AuthStatus.signedIn ? 'My Records' : 'Patient Portal Login'),
                  );
                }),
                const SizedBox(height: 24),
              ]),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String text;
  const _SectionTitle(this.text);
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Text(text, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800, color: AppColors.navy)),
      );
}

class _ServiceChip extends StatelessWidget {
  final String label;
  final IconData icon;
  const _ServiceChip({required this.label, required this.icon});
  @override
  Widget build(BuildContext context) => Container(
        width: 160,
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10), border: Border.all(color: const Color(0xFFE4E9EF))),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, color: AppColors.blue),
          const SizedBox(height: 8),
          Text(label, style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppColors.navy)),
        ]),
      );
}

class _ContactForm extends StatefulWidget {
  const _ContactForm();
  @override
  State<_ContactForm> createState() => _ContactFormState();
}

class _ContactFormState extends State<_ContactForm> {
  final _fname = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _message = TextEditingController();
  bool _sending = false;
  String? _status;

  Future<void> _submit() async {
    if (_fname.text.trim().isEmpty || _phone.text.trim().isEmpty) {
      setState(() => _status = 'Please fill in all required fields.');
      return;
    }
    setState(() {
      _sending = true;
      _status = null;
    });
    try {
      await context.read<AuthState>().repo.sendContact(
            fname: _fname.text.trim(),
            email: _email.text.trim(),
            phone: _phone.text.trim(),
            message: _message.text.trim(),
          );
      setState(() => _status = "Thank you! We'll contact you shortly.");
      _fname.clear();
      _email.clear();
      _phone.clear();
      _message.clear();
    } on ApiException catch (e) {
      setState(() => _status = e.message);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(children: [
          TextField(controller: _fname, decoration: const InputDecoration(labelText: 'First Name *')),
          const SizedBox(height: 10),
          TextField(controller: _email, decoration: const InputDecoration(labelText: 'Email *'), keyboardType: TextInputType.emailAddress),
          const SizedBox(height: 10),
          TextField(controller: _phone, decoration: const InputDecoration(labelText: 'Phone *'), keyboardType: TextInputType.phone),
          const SizedBox(height: 10),
          TextField(controller: _message, decoration: const InputDecoration(labelText: 'Message'), maxLines: 3),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(onPressed: _sending ? null : _submit, child: Text(_sending ? 'Sending…' : 'Send Message')),
          ),
          if (_status != null) ...[
            const SizedBox(height: 8),
            Text(_status!, style: const TextStyle(color: AppColors.gray, fontSize: 13)),
          ],
        ]),
      ),
    );
  }
}
