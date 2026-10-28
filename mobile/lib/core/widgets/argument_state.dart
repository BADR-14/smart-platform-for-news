import 'package:flutter/widgets.dart';
import 'package:get/get.dart';

/// أساس الصفحات التي يمكن فتح أكثر من نسخة منها فوق بعض (تفاصيل منشور، صفحة شركة ...).
///
/// كل نسخة لها متحكمها الخاص، موسوم بمعرّف العنصر الممرَّر في `arguments`.
/// نقرأ المعرّف مرة واحدة عند فتح الصفحة، لأن `Get.arguments` يتغير
/// عند فتح أي صفحة أخرى فوقها.
abstract class ArgumentState<W extends StatefulWidget, C extends GetxController> extends State<W> {
  late final C controller;

  @override
  void initState() {
    super.initState();
    controller = Get.find<C>(tag: '${Get.arguments}');
  }
}
