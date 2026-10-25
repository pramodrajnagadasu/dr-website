import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';

import '../../api/api_client.dart';
import '../../state/auth_state.dart';
import '../../theme/app_theme.dart';

class PaymentScreen extends StatefulWidget {
  final String ref;
  const PaymentScreen({super.key, required this.ref});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  late final Razorpay _razorpay;
  bool _loading = true;
  bool _paying = false;
  String? _error;
  Map<String, dynamic>? _order;

  @override
  void initState() {
    super.initState();
    _razorpay = Razorpay();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _onSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _onError);
    _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _onExternalWallet);
    _load();
  }

  @override
  void dispose() {
    _razorpay.clear();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final order = await context.read<AuthState>().repo.getPaymentOrder(widget.ref);
      setState(() {
        _order = order;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  void _startPayment() {
    final order = _order!;
    setState(() => _paying = true);
    _razorpay.open({
      'key': order['razorpay_key_id'],
      'order_id': order['razorpay_order_id'],
      'amount': order['amount_paise'],
      'currency': order['currency'] ?? 'INR',
      'name': 'Dr. Prasanna Kumar K',
      'description': 'Consultation fee — ${widget.ref}',
      'prefill': {
        'contact': order['patient_mobile'] ?? '',
        'email': order['patient_email'] ?? '',
      },
      'theme': {'color': '#1a5fb4'},
    });
  }

  Future<void> _onSuccess(PaymentSuccessResponse response) async {
    try {
      final result = await context.read<AuthState>().repo.verifyPayment(
            ref: widget.ref,
            orderId: response.orderId ?? '',
            paymentId: response.paymentId ?? '',
            signature: response.signature ?? '',
          );
      setState(() {
        _order = {..._order!, 'status': (result['payment'] as Map)['status']};
        _paying = false;
      });
      await context.read<AuthState>().refreshAppointments();
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _paying = false;
      });
    }
  }

  void _onError(PaymentFailureResponse response) {
    setState(() {
      _error = 'Payment failed: ${response.message ?? 'please try again.'}';
      _paying = false;
    });
  }

  void _onExternalWallet(ExternalWalletResponse response) {
    setState(() => _paying = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Consultation Payment')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _order == null
              ? Center(child: Text(_error ?? 'Could not load payment details.'))
              : Padding(
                  padding: const EdgeInsets.all(16),
                  child: Card(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('Reference: ${widget.ref}', style: const TextStyle(color: AppColors.gray)),
                        const SizedBox(height: 6),
                        Text(
                          '₹${((_order!['amount_paise'] as int) / 100).toStringAsFixed(2)}',
                          style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: AppColors.navy),
                        ),
                        const SizedBox(height: 20),
                        if (_order!['status'] == 'paid')
                          const Row(children: [
                            Icon(Icons.check_circle, color: AppColors.success),
                            SizedBox(width: 8),
                            Text('Payment received. Thank you!', style: TextStyle(color: AppColors.success, fontWeight: FontWeight.w600)),
                          ])
                        else ...[
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: _paying ? null : _startPayment,
                              icon: const Icon(Icons.lock_outline),
                              label: Text(_paying ? 'Processing…' : 'Pay Securely'),
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Text('Supports UPI, debit and credit cards via Razorpay.',
                              style: TextStyle(color: AppColors.gray, fontSize: 12)),
                        ],
                        if (_error != null) ...[
                          const SizedBox(height: 12),
                          Text(_error!, style: const TextStyle(color: AppColors.danger, fontSize: 13)),
                        ],
                      ]),
                    ),
                  ),
                ),
    );
  }
}
