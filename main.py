A = 'Pedeasure'
B = 'Bebelac'
C = "SGM"

satu = 'Kecil'
dua = 'Sedang'
tiga = 'Besar'

pembeli = input('Apakah anda member(Ya/Tidak) : ')
kode_susu = input('Masukkan kode susu : ')
ukuran_klg = input('Masukkan ukuran kaleng : ')
jlh_klg_dibeli = input('Masukkan banyak kaleng yg dibeli :')

Pediasure = [250000, 350000, 450000]
Bebelac =   [150000, 250000, 350000]
SGM =       [100000, 200000, 300000]

if kode_susu  == A :
    if ukuran_klg == 1:
        harga = Pediasure[0]       
    elif ukuran_klg == 2:
        harga = Pediasure[1]
    elif ukuran_klg == 3:
        harga = Pediasure[2]
elif kode_susu == B :
    if ukuran_klg == 1:
        harga = Bebelac[0]
    elif ukuran_klg == 2:
        harga = Bebelac[1]
    elif ukuran_klg == 3:
        harga = Bebelac[2]
elif kode_susu == C:
    if ukuran_klg == 1:
        harga = SGM[0]
    elif ukuran_klg == 2:
        harga = SGM[1]
    elif ukuran_klg == 3:
        harga = SGM[2]

total = jlh_klg_dibeli * harga

if pembeli == 'Ya':
    diskon_1 = total * 0.1
    if total > 150000:
        diskon_2 = total * 0.5
# else :