import 'dart:io';
import 'dart:typed_data';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../api/api_client.dart';
import '../../models/appointment.dart';
import '../../state/auth_state.dart';
import '../../theme/app_theme.dart';

class RecordScreen extends StatefulWidget {
  final String ref;
  const RecordScreen({super.key, required this.ref});

  @override
  State<RecordScreen> createState() => _RecordScreenState();
}

class _RecordScreenState extends State<RecordScreen> {
  bool _loading = true;
  List<RecordEntry> _entries = [];
  String? _error;

  final _descCtrl = TextEditingController();
  PlatformFile? _pickedFile;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _descCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final entries = await context.read<AuthState>().repo.getRecord(widget.ref);
      setState(() {
        _entries = entries;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    }
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
    );
    if (result.isNotEmpty) {
      setState(() => _pickedFile = result.first);
    }
  }

  Future<void> _submit() async {
    if (_descCtrl.text.trim().isEmpty && _pickedFile == null) {
      setState(() => _error = 'Please add a note or upload a file.');
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await context.read<AuthState>().repo.addRecordEntry(
            ref: widget.ref,
            description: _descCtrl.text.trim(),
            file: _pickedFile != null ? File(_pickedFile!.path!) : null,
          );
      _descCtrl.clear();
      _pickedFile = null;
      await _load();
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Record ${widget.ref}')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text('${_entries.length} entr${_entries.length != 1 ? 'ies' : 'y'}',
                    style: const TextStyle(color: AppColors.gray, fontSize: 13)),
                const SizedBox(height: 10),
                if (_entries.isEmpty)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 24),
                    child: Center(child: Text('No entries yet. Add a note or upload a file below.', style: TextStyle(color: AppColors.gray))),
                  )
                else
                  ..._entries.map((e) => _EntryTile(entry: e, ref: widget.ref)),
                const SizedBox(height: 20),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const Text('Add Note / Upload File', style: TextStyle(fontWeight: FontWeight.w700, color: AppColors.navy)),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _descCtrl,
                        maxLines: 3,
                        decoration: const InputDecoration(hintText: 'e.g. Symptom update, questions for doctor…'),
                      ),
                      const SizedBox(height: 10),
                      Row(children: [
                        OutlinedButton.icon(
                          onPressed: _pickFile,
                          icon: const Icon(Icons.attach_file),
                          label: Text(_pickedFile?.name ?? 'Choose File'),
                        ),
                      ]),
                      const SizedBox(height: 6),
                      const Text('JPG, PNG, GIF, WEBP, PDF • Max 10 MB', style: TextStyle(color: AppColors.gray, fontSize: 11)),
                      const SizedBox(height: 14),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(onPressed: _saving ? null : _submit, child: Text(_saving ? 'Saving…' : 'Save Entry')),
                      ),
                      if (_error != null) ...[
                        const SizedBox(height: 8),
                        Text(_error!, style: const TextStyle(color: AppColors.danger, fontSize: 13)),
                      ],
                    ]),
                  ),
                ),
              ],
            ),
    );
  }
}

class _EntryTile extends StatelessWidget {
  final RecordEntry entry;
  final String ref;
  const _EntryTile({required this.entry, required this.ref});

  @override
  Widget build(BuildContext context) {
    final byDoctor = entry.by == 'doctor';
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      color: byDoctor ? const Color(0xFFF0F6FF) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            CircleAvatar(
              radius: 14,
              backgroundColor: byDoctor ? AppColors.blue : AppColors.gray,
              child: Icon(byDoctor ? Icons.medical_services : Icons.person, size: 14, color: Colors.white),
            ),
            const SizedBox(width: 8),
            Text(byDoctor ? 'Dr. Prasanna Kumar K' : 'You', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
            const Spacer(),
            Text(entry.createdAt, style: const TextStyle(color: AppColors.gray, fontSize: 11)),
          ]),
          if (entry.description != null && entry.description!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(entry.description!),
          ],
          if (entry.filename != null) ...[
            const SizedBox(height: 8),
            if (entry.isImage)
              _RecordImage(ref: ref, filename: entry.filename!.split('/').last)
            else
              Row(children: [
                const Icon(Icons.picture_as_pdf, color: AppColors.danger, size: 18),
                const SizedBox(width: 6),
                Flexible(child: Text(entry.originalName ?? 'Document', overflow: TextOverflow.ellipsis)),
              ]),
          ],
        ]),
      ),
    );
  }
}

class _RecordImage extends StatelessWidget {
  final String ref;
  final String filename;
  const _RecordImage({required this.ref, required this.filename});

  @override
  Widget build(BuildContext context) {
    final repo = context.read<AuthState>().repo;
    return FutureBuilder<List<int>>(
      future: repo.getRecordFileBytes(ref, filename),
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const SizedBox(height: 120, child: Center(child: CircularProgressIndicator(strokeWidth: 2)));
        }
        return ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Image.memory(Uint8List.fromList(snapshot.data!), fit: BoxFit.cover, height: 180, width: double.infinity),
        );
      },
    );
  }
}
