import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'api/api_client.dart';
import 'screens/booking/book_appointment_screen.dart';
import 'screens/home/home_screen.dart';
import 'screens/payment/payment_screen.dart';
import 'screens/portal/portal_screen.dart';
import 'screens/portal/record_screen.dart';
import 'state/auth_state.dart';
import 'theme/app_theme.dart';

void main() {
  runApp(const AppRoot());
}

class AppRoot extends StatelessWidget {
  const AppRoot({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AuthState(ApiClient())..bootstrap(),
      child: const DrPrasannaApp(),
    );
  }
}

final _router = GoRouter(routes: [
  GoRoute(path: '/', builder: (context, state) => const HomeScreen()),
  GoRoute(path: '/book', builder: (context, state) => const BookAppointmentScreen()),
  GoRoute(path: '/portal', builder: (context, state) => const PortalScreen()),
  GoRoute(
    path: '/record/:ref',
    builder: (context, state) => RecordScreen(ref: state.pathParameters['ref']!),
  ),
  GoRoute(
    path: '/pay/:ref',
    builder: (context, state) => PaymentScreen(ref: state.pathParameters['ref']!),
  ),
]);

class DrPrasannaApp extends StatelessWidget {
  const DrPrasannaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'Dr. Prasanna Kumar K',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      routerConfig: _router,
    );
  }
}
