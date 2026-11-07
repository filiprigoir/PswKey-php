#define FFI_SCOPE "SHUFFLEINDICE"
#define FFI_LIB "./src/Core/Component/FFI/Compiled/shuffleindice.so"

typedef unsigned char uint8_t;
typedef unsigned long size_t;

int shuffle_indices_secure(
    const uint8_t *rand_bytes,
    size_t rand_len,
    size_t input_len,
    size_t required_len,
    uint8_t *out_array
);