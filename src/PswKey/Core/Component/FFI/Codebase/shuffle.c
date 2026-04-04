#include <stdint.h>
#include <stdlib.h>
#include <string.h>
#include "limits.h"

#if defined(_WIN32) || defined(__CYGWIN__)
  #define EXPORT __declspec(dllexport)
#else
  #define EXPORT
#endif

#ifdef __cplusplus
extern "C" {
#endif

#define SHUFFLE_LEN_MIN 4
#define SHUFFLE_LEN_MAX 256

int validate_shuffle_length(size_t input_len) {
    return (input_len < SHUFFLE_LEN_MIN || input_len > SHUFFLE_LEN_MAX) ? -1 : 0;
}

static void secure_zero(void *p, size_t n) {
    if (p) {
        volatile uint8_t *ptr = (volatile uint8_t*)p;
        while (n--) *ptr++ = 0;
    }
}

/**
 * Shuffle indices [0..input_len-1], slice to required_len
 */
EXPORT int shuffle_indices_secure(
    const uint8_t *rand_bytes, size_t rand_len,
    size_t input_len,
    size_t required_len,
    uint8_t *out_array //indices output
) {
    if (!rand_bytes || rand_len == 0 || !out_array) return -1;
    if (required_len == 0 || required_len > input_len) return -2;
    if (validate_shuffle_length(input_len) != 0) return -3;

    size_t pos = 0;

    /* ---------- work buffer ---------- */
    uint8_t *work_buffer = malloc(input_len * sizeof(uint8_t));
    if (!work_buffer) {
        return -4; // no safe INPUT fallback for indices
    }

    /* ---------- init indices ---------- */
    for (size_t i = 0; i < input_len; i++) {
        work_buffer[i] = (uint8_t)i;
    }

    /* ---------- Fisher–Yates ---------- */
    for (size_t i = input_len - 1; i > 0; i--) {
        int val = -1;
        size_t m = i + 1;

        while (val < 0) {
            if (pos >= rand_len) pos = 0;
            uint8_t byte = rand_bytes[pos++];

            if (byte < LIMITS_TABLE[m])
                val = byte % m;

            secure_zero(&byte, 1);
        }

        uint8_t tmp = work_buffer[i];
        work_buffer[i] = work_buffer[val];
        work_buffer[val] = tmp;

        secure_zero(&tmp, sizeof(tmp));
        secure_zero(&val, sizeof(val));
    }

    /* ---------- slice ---------- */
    if (required_len == input_len) {
        memcpy(out_array, work_buffer, input_len * sizeof(uint8_t));
    } else {
        int start_idx = -1;
        while (start_idx < 0) {
            if (pos >= rand_len) pos = 0;
            uint8_t byte = rand_bytes[pos++];

            if (byte < LIMITS_TABLE[input_len])
                start_idx = byte % input_len;

            secure_zero(&byte, 1);
        }

        for (size_t i = 0; i < required_len; i++) {
            out_array[i] = work_buffer[(start_idx + i) % input_len];
        }

        secure_zero(&start_idx, sizeof(start_idx));
    }

    /* ---------- wipe ---------- */
    secure_zero(work_buffer, input_len * sizeof(uint8_t));
    free(work_buffer);

    return 0;
}

#ifdef __cplusplus
}
#endif