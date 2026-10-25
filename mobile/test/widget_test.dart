// Smoke test: the app boots and shows the home screen's brand name.

import 'package:flutter_test/flutter_test.dart';

import 'package:drprasanna_app/main.dart';

void main() {
  testWidgets('App boots to the home screen', (WidgetTester tester) async {
    await tester.pumpWidget(const AppRoot());
    await tester.pump();

    expect(find.text('Dr. Prasanna Kumar K'), findsWidgets);
  });
}
