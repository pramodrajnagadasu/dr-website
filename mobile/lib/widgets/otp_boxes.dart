import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Six single-digit boxes with auto-advance, matching the website's OTP UI.
class OtpBoxes extends StatefulWidget {
  final ValueChanged<String> onChanged;
  const OtpBoxes({super.key, required this.onChanged});

  @override
  State<OtpBoxes> createState() => OtpBoxesState();
}

class OtpBoxesState extends State<OtpBoxes> {
  final _controllers = List.generate(6, (_) => TextEditingController());
  final _nodes = List.generate(6, (_) => FocusNode());

  void clear() {
    for (final c in _controllers) {
      c.clear();
    }
    _nodes[0].requestFocus();
  }

  String get value => _controllers.map((c) => c.text).join();

  @override
  void dispose() {
    for (final c in _controllers) {
      c.dispose();
    }
    for (final n in _nodes) {
      n.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(6, (i) {
        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: 5),
          child: SizedBox(
            width: 44,
            height: 52,
            child: TextField(
              controller: _controllers[i],
              focusNode: _nodes[i],
              textAlign: TextAlign.center,
              keyboardType: TextInputType.number,
              maxLength: 1,
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: const InputDecoration(counterText: ''),
              onChanged: (v) {
                if (v.isNotEmpty && i < 5) _nodes[i + 1].requestFocus();
                if (v.isEmpty && i > 0) _nodes[i - 1].requestFocus();
                widget.onChanged(value);
              },
            ),
          ),
        );
      }),
    );
  }
}
