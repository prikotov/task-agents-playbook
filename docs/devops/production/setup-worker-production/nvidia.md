# Установка NVIDIA-драйверов на AlmaLinux 9/10 (native NVIDIA support)

Инструкция основана на официальном блоге AlmaLinux  
[“AlmaLinux OS 9 and 10 – Now with Native Support for NVIDIA”](https://almalinux.org/blog/2025-08-06-announcing-native-nvidia-suport/).

## 1. Включить репозитории NVIDIA

```bash
sudo dnf install -y almalinux-release-nvidia-driver
```

## 2. Установить открытый драйвер NVIDIA

```bash
sudo dnf install -y nvidia-open-kmod nvidia-driver
```

Рекомендуется перезагрузить систему:

```bash
sudo reboot
```

Либо, если уже загружено последнее ядро, можно подгрузить модуль без ребута:

```bash
sudo modprobe nvidia_drm
```

## 3. Установить утилиту nvidia-smi

```bash
sudo dnf install -y nvidia-driver-cuda
```

## 4. Установить CUDA Toolkit и компилятор nvcc

```bash
sudo dnf install -y cuda-toolkit-13-1 cuda-nvcc-13-1
```

Проверить наличие компилятора:

```bash
nvcc --version
```

## 5. Настроить PATH и компилятор CUDA только для текущего пользователя

Добавить CUDA в PATH и указать компилятор CUDA (nvcc) **только для текущего пользователя**:

```bash
echo 'export PATH=/usr/local/cuda-13.1/bin:$PATH' >> ~/.bashrc
echo 'export CUDACXX=/usr/local/cuda-13.1/bin/nvcc' >> ~/.bashrc
source ~/.bashrc
```

Проверить:

```bash
which nvcc
nvcc --version
echo "$CUDACXX"
```

## 6. Проверка работы драйвера

Проверить, что GPU видна:

```bash
nvidia-smi
```

Проверить, что модуль загружен:

```bash
lsmod | grep -i nvidia
```
